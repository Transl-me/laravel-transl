<?php

declare(strict_types=1);

namespace Transl\Api\Requests;

use Transl\Api\Client;
use Transl\Support\Branch;
use GuzzleHttp\Promise\Promise;
use Transl\Support\Push\PushBatch;
use Transl\Support\Push\PushChunk;
use Illuminate\Http\Client\Response;
use Transl\Config\ProjectConfiguration;
use Transl\Support\Concerns\Instanciable;
use Illuminate\Http\Client\PendingRequest;
use Transl\Api\Responses\Commands\PullResponse;

class CommandRequests
{
    use Instanciable;

    public function pull(ProjectConfiguration $project, Branch $branch, array $query = []): PullResponse
    {
        return PullResponse::fromClientResponse(
            $this->http($project, $branch)->get("/commands/{$branch->name}/pull", $query),
        );
    }

    /**
     * @param (callable(PushChunk $chunk): void)|null $onPushed
     */
    public function push(ProjectConfiguration $project, Branch $branch, PushBatch $batch, ?callable $onPushed = null, array $meta = []): void
    {
        Client::new()
            ->withProject($project)
            ->withBranch($branch)
            ->pool($batch->pool, function (PendingRequest $request, PushChunk $chunk) use ($branch, $batch, $onPushed, $meta): void {
                /** @var Promise $promise */
                $promise = $request->post("/commands/{$branch->name}/push", [
                    'batch' => [
                        'id' => $batch->id,
                        'max_pool_size' => PushBatch::maxPoolSize(),
                        'max_chunk_size' => PushBatch::maxChunkSize(),
                        'total_pushable' => $batch->totalPushable(),
                        'total_pushed' => $batch->totalPushed(),
                    ],
                    'chunk' => $chunk->toArray(),
                    'meta' => $meta,
                ]);

                $promise->then(function (Response $response) use ($onPushed, $chunk): void {
                    if (!$response->successful()) {
                        return;
                    }

                    if ($onPushed) {
                        $onPushed($chunk);
                    }
                });
            });
    }

    public function pushEnd(ProjectConfiguration $project, Branch $branch, PushBatch $batch, array $meta = []): void
    {
        $this->http($project, $branch)->post("/commands/{$branch->name}/push/end", [
            'batch' => [
                'id' => $batch->id,
                'total_pushed' => $batch->totalPushed(),
            ],
            'meta' => $meta,
        ]);
    }

    public function initStart(ProjectConfiguration $project, Branch $branch, Branch $defaultBranch): void
    {
        $this->http($project, $branch)->post('/commands/init/start', [
            'locale' => [
                'default' => $project->options->locale->default,
                'fallback' => $project->options->locale->fallback,
            ],
            'branching' => [
                'default_branch_name' => $defaultBranch->name,
            ],
        ]);
    }

    public function initEnd(ProjectConfiguration $project, Branch $branch): void
    {
        $this->http($project, $branch)->post('/commands/init/end');
    }

    protected function http(ProjectConfiguration $project, Branch $branch): PendingRequest
    {
        return Client::new()->withProject($project)->withBranch($branch)->http();
    }
}
