<?php

declare(strict_types=1);

use Transl\Facades\Transl;
use Transl\Support\Branch;
use Illuminate\Support\Arr;
use Transl\Config\Configuration;
use Illuminate\Support\Collection;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Transl\Actions\Reports\SendMissingTranslationKeyReportAction;
use Transl\Support\Reports\MissingTranslationKeys\MissingTranslationKey;
use Transl\Support\Reports\MissingTranslationKeys\MissingTranslationKeys;
use Transl\Support\Reports\MissingTranslationKeys\MissingTranslationKeyReport;

beforeEach(function (): void {
    config()->set('app.debug', true);

    $this->translConfig = config('transl');

    config()->set('transl.projects', [
        [
            'auth_key' => 'project1',
        ],
        [
            'auth_key' => 'project2',
        ],
    ]);

    Configuration::refreshInstance(config('transl'));
});

afterEach(function (): void {
    config()->set('transl', $this->translConfig);

    Configuration::refreshInstance(config('transl'));
});

it('works', function (): void {
    Http::fake([
        'https://api.transl.me/v0/reports/missing-translation-keys' => Http::response(),
    ]);

    $projects = Transl::config()->projects();
    $branch1 = Branch::asCurrent('yolo');
    $branch2 = Branch::asFallback('yo');

    $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
    $missingKey2 = MissingTranslationKey::new('auth.password', [], 'ht', true);
    $missingKeyReport1 = MissingTranslationKeyReport::new($projects->first(), $branch1, $missingKey);
    $missingKeyReport2 = MissingTranslationKeyReport::new($projects->last(), $branch1, $missingKey);
    $missingKeyReport3 = MissingTranslationKeyReport::new($projects->first(), $branch2, $missingKey);
    $missingKeyReport4 = MissingTranslationKeyReport::new($projects->last(), $branch2, $missingKey);
    $missingKeyReport5 = MissingTranslationKeyReport::new($projects->first(), $branch2, $missingKey2);

    $missingKeys = (new MissingTranslationKeys())
        ->add('auth.password', [], 'en', true, $projects->first(), $branch1)
        ->add('auth.password', [], 'en', true, $projects->first(), $branch1)
        ->add('auth.password', [], 'en', true, $projects->last(), $branch1)
        ->add('auth.password', [], 'en', true, $projects->first(), $branch2)
        ->add('auth.password', [], 'en', true, $projects->last(), $branch2)
        ->add('auth.password', [], 'ht', true, $projects->first(), $branch2);

    (new SendMissingTranslationKeyReportAction())->execute($missingKeys->queued());

    Http::assertSentCount(4);

    $sent = collect(Http::recorded())
        ->flatten()
        ->filter(static fn (Request|Response $item): bool => $item instanceof Request)
        ->map(static fn (Request $request): array => $request->data())
        ->values();

    expect($sent->all())->toEqual([
        [
            'keys' => [
                $missingKeyReport1->key->id() => $missingKeyReport1->key,
            ],
        ],
        [
            'keys' => [
                $missingKeyReport2->key->id() => $missingKeyReport2->key,
            ],
        ],
        [
            'keys' => [
                $missingKeyReport3->key->id() => $missingKeyReport3->key,
                $missingKeyReport5->key->id() => $missingKeyReport5->key,
            ],
        ],
        [
            'keys' => [
                $missingKeyReport4->key->id() => $missingKeyReport4->key,
            ],
        ],
    ]);
});

it('sents the reports to the correct project & branch', function (): void {
    Http::fake([
        'https://api.transl.me/v0/reports/missing-translation-keys' => Http::response(),
    ]);

    $projects = Transl::config()->projects();
    $branch1 = Branch::asCurrent('yolo');
    $branch2 = Branch::asFallback('yo');

    $missingKeys = (new MissingTranslationKeys())
        ->add('auth.password', [], 'en', true, $projects->first(), $branch1)
        ->add('auth.password', [], 'en', true, $projects->first(), $branch1)
        ->add('auth.password', [], 'en', true, $projects->last(), $branch1)
        ->add('auth.password', [], 'en', true, $projects->first(), $branch2)
        ->add('auth.password', [], 'en', true, $projects->last(), $branch2)
        ->add('auth.password', [], 'ht', true, $projects->first(), $branch2);

    (new SendMissingTranslationKeyReportAction())->execute($missingKeys->queued());

    /** @var Collection<array-key, Request> $requests */
    $requests = collect(Http::recorded())
        ->flatten()
        ->filter(static fn (Request|Response $item): bool => $item instanceof Request)
        ->values();

    $projectAuthKeys = $requests
        ->map(static fn (Request $request): array => Arr::only($request->headers(), 'Authorization'));
    $branchNames = $requests
        ->map(static fn (Request $request): array => Arr::only($request->headers(), 'X-Transl-Branch-Name'));

    expect($projectAuthKeys->all())->toEqual([
        [
            'Authorization' => [
                "Bearer {$projects->first()->auth_key}",
            ],
        ],
        [
            'Authorization' => [
                "Bearer {$projects->last()->auth_key}",
            ],
        ],
        [
            'Authorization' => [
                "Bearer {$projects->first()->auth_key}",
            ],
        ],
        [
            'Authorization' => [
                "Bearer {$projects->last()->auth_key}",
            ],
        ],
    ]);
    expect($branchNames->all())->toEqual([
        [
            'X-Transl-Branch-Name' => [
                $branch1->name,
            ],
        ],
        [
            'X-Transl-Branch-Name' => [
                $branch1->name,
            ],
        ],
        [
            'X-Transl-Branch-Name' => [
                $branch2->name,
            ],
        ],
        [
            'X-Transl-Branch-Name' => [
                $branch2->name,
            ],
        ],
    ]);
});

it('sets the necessary HTTP headers & custom Transl HTTP headers', function (): void {
    Http::fake([
        'https://api.transl.me/v0/reports/missing-translation-keys' => Http::response(),
    ]);

    $project = Transl::config()->projects()->first();
    $branch = Branch::asCurrent('yolo');

    $missingKeys = (new MissingTranslationKeys())
        ->add('auth.password', [], 'ht', true, $project, $branch)
        ->add('auth.password', [], 'jp', true, $project, $branch)
        ->add('auth.password', [], 'it', true, $project, $branch);

    (new SendMissingTranslationKeyReportAction())->execute($missingKeys->queued());

    $versions = Transl::versions();
    $packageName = Transl::PACKAGE_NAME;

    $userAgentPackageName = str_replace('/', '___', $packageName);
    $userAgent = "{$userAgentPackageName}/{$versions->package} laravel/{$versions->laravel} php/{$versions->php}";

    Http::assertSent(static function (Request $request) use ($project, $branch, $versions, $packageName, $userAgent): bool {
        return $request->hasHeaders([
            'User-Agent' => $userAgent,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',

            'Authorization' => "Bearer {$project->auth_key}",

            'X-Transl-Branch-Name' => $branch->name,
            'X-Transl-Branch-Provenance' => $branch->provenance(),

            'X-Transl-Package-Name' => $packageName,
            'X-Transl-Package-Version' => $versions->package,
            'X-Transl-Framework-Name' => 'Laravel',
            'X-Transl-Framework-Version' => $versions->laravel,
            'X-Transl-Language-Name' => 'PHP',
            'X-Transl-Language-Version' => $versions->php,
        ]);
    });
});
