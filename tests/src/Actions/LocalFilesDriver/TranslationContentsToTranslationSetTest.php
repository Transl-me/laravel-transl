<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Transl\Support\TranslationSet;
use Transl\Drivers\LocalFilesDriver;
use Transl\Support\TranslationLineCollection;
use Transl\Actions\LocalFilesDriver\TranslationContentsToTranslationSetAction;

it('works', function (): void {
    $data = [
        'contents' => __('auth', [], 'en'),
        'locale' => 'en',
        'group' => 'auth',
        'namespace' => null,
        'meta' => [
            'some_metadata' => 'some_value',
        ],
    ];

    $lines = TranslationLineCollection::fromRawTranslationLines($data['contents']);

    $isntance = (new TranslationContentsToTranslationSetAction())->usingDriver(new LocalFilesDriver());
    $result = $isntance->execute(...$data);

    expect($result instanceof TranslationSet)->toEqual(true);

    expect($result->lines)->toEqual($lines);
    expect($result->locale)->toEqual($data['locale']);
    expect($result->group)->toEqual($data['group']);
    expect($result->namespace)->toEqual($data['namespace']);
    expect($result->meta)->toEqual($data['meta']);
    expect($result->toArray())->toEqual([
        ...Arr::except($data, 'contents'),
        'lines' => $lines->toArray(),
    ]);

    expect($result)->toMatchSnapshot();
});
