<?php

declare(strict_types=1);

namespace Transl\Api;

use Closure;
use Exception;
use Throwable;
use Transl\Facades\Transl;
use Transl\Support\Branch;
use Transl\Support\Versions;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Transl\Config\ProjectConfiguration;
use GuzzleHttp\Exception\GuzzleException;
use Transl\Support\Concerns\Instanciable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * @phpstan-consistent-constructor
 */
class Client
{
    use Instanciable;

    public const API_VERSION = 'v0';
    public const BASE_URL = 'https://api.transl.me/' . self::API_VERSION;

    protected static bool $retryOnTooManyRequests = true;

    /**
     * @var (Closure(): PendingRequest)|null
     */
    protected static ?Closure $makePendingRequestCallback = null;

    protected ?string $authKey = null;
    protected ?Branch $branch = null;

    public function __construct()
    {
    }

    public static function new(): static
    {
        return new static();
    }

    /**
     * Whether to retry when rate limits are exceeded or not.
     */
    public static function shouldRetryOnTooManyRequests(bool $value = true): void
    {
        static::$retryOnTooManyRequests = $value;
    }

    /**
     * @param (Closure(): PendingRequest)|null $callback
     */
    public static function makePendingRequestUsing(?Closure $callback): void
    {
        static::$makePendingRequestCallback = $callback;
    }

    /**
     * Sets the authentication key of the project to target.
     */
    public function withAuthKey(string $key): static
    {
        $this->authKey = $key;

        return $this;
    }

    /**
     * Sets the project to target from which the authentication
     * key will be extracted..
     */
    public function withProject(ProjectConfiguration $project): static
    {
        return $this->withAuthKey($project->auth_key);
    }

    /**
     * Sets the branch of the project to target.
     */
    public function withBranch(Branch $branch): static
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * Request setup.
     */
    public function http(): PendingRequest
    {
        $request = $this->baseHttp();

        if ($this->authKey) {
            $request->withToken($this->authKey);
        }

        if ($this->branch) {
            $request->withHeaders([
                'X-Transl-Branch-Name' => $this->branch->name,
                'X-Transl-Branch-Provenance' => $this->branch->provenance(),
            ]);
        }

        if (static::$retryOnTooManyRequests) {
            $this->setupRetry($request);
        }

        return $request;
    }

    /**
     * Make concurrent requests.
     *
     * @template TItem
     *
     * @param iterable<array-key, TItem> $items
     * @param callable(PendingRequest $request, TItem $item): void $callback
     * @return (Response|GuzzleException)[]
     */
    public function pool(iterable $items, callable $callback): array
    {
        $responses = $this->basePool($items, $callback);

        if ($this->shouldPatchAsyncRequestExceptionHandling()) {
            $this->patchAsyncRequestExceptionHandling($responses);
        }

        return $responses;
    }

    protected function baseHttp(): PendingRequest
    {
        $versions = Transl::versions();
        $packageName = Transl::PACKAGE_NAME;

        $request = static::$makePendingRequestCallback
            ? (static::$makePendingRequestCallback)()
            : Http::baseUrl(static::BASE_URL);

        return $request
            ->withUserAgent($this->makeUserAgent($packageName, $versions))
            ->withHeaders([
                'X-Transl-Package-Name' => $packageName,
                'X-Transl-Package-Version' => $versions->package,
                'X-Transl-Framework-Name' => 'Laravel',
                'X-Transl-Framework-Version' => $versions->laravel,
                'X-Transl-Language-Name' => 'PHP',
                'X-Transl-Language-Version' => $versions->php,
            ])
            ->acceptJson()
            ->asJson()
            ->throw();
    }

    /**
     * @template TItem
     *
     * @param iterable<array-key, TItem> $items
     * @param callable(PendingRequest $request, TItem $item): void $callback
     * @return (Response|GuzzleException)[]
     */
    protected function basePool(iterable $items, callable $callback): array
    {
        $pendingRequest = $this->http();

        // Extracting out the `PendingRequest#baseUrl` non-public property value
        $baseUrl = Closure::bind(
            function (): string {
                /** @var PendingRequest $this */
                return $this->baseUrl;
            },
            $pendingRequest,
            PendingRequest::class,
        )();

        $options = $pendingRequest->getOptions();

        return $pendingRequest->pool(function (Pool $pool) use ($items, $callback, $baseUrl, $options): void {
            foreach ($items as $item) {
                /** @var PendingRequest $request */
                $request = $pool->baseUrl($baseUrl)->withOptions($options);

                if (static::$retryOnTooManyRequests) {
                    $this->setupRetry($request);
                }

                $callback($request, $item);
            }
        });
    }

    protected function makeUserAgent(string $packageName, Versions $versions): string
    {
        $packageName = str_replace('/', '___', $packageName);

        return "{$packageName}/{$versions->package} laravel/{$versions->laravel} php/{$versions->php}";
    }

    protected function setupRetry(PendingRequest $request): PendingRequest
    {
        return $request->retry(2, 0, $this->makeTooManyRequestsRetryHandler());
    }

    protected function makeTooManyRequestsRetryHandler(): Closure
    {
        return static function (Exception $exception, PendingRequest $request): bool {
            if (!($exception instanceof RequestException)) {
                return false;
            }

            if ($exception->response->status() !== SymfonyResponse::HTTP_TOO_MANY_REQUESTS) {
                return false;
            }

            $retryAfter = (int) $exception->response->header('Retry-After');

            if (!$retryAfter) {
                return false;
            }

            sleep($retryAfter);

            return true;
        };
    }

    protected function shouldPatchAsyncRequestExceptionHandling(): bool
    {
        return version_compare(app()->version(), '11.0.0') < 0;
    }

    /**
     * @param (Response|GuzzleException)[] $responses
     */
    protected function patchAsyncRequestExceptionHandling(array $responses): void
    {
        foreach ($responses as $response) {
            if ($response instanceof Throwable) {
                throw $response;
            }

            $response->throw();
        }
    }
}
