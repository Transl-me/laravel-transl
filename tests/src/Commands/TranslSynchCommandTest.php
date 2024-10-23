<?php

declare(strict_types=1);

use Transl\Commands\TranslPullCommand;
use Transl\Commands\TranslPushCommand;
use Illuminate\Support\Facades\Artisan;
use Transl\Commands\TranslSynchCommand;

it('uses `TranslPullCommand` to pull translation sets from Transl', function (): void {
    app()->singleton(TranslPullCommand::class, function (): TranslPullCommand {
        return new class () extends TranslPullCommand {
            public readonly bool $used;

            public function handle(): int
            {
                $this->used = true;

                return TranslPullCommand::SUCCESS;
            }
        };
    });
    app()->singleton(TranslPushCommand::class, static function (): TranslPushCommand {
        return new class () extends TranslPushCommand {
            public function handle(): int
            {
                return TranslPushCommand::SUCCESS;
            }
        };
    });

    Artisan::call(TranslSynchCommand::class);

    expect(app(TranslPullCommand::class)->used)->toEqual(true);
});

it('uses `TranslPushCommand` to push translation sets to Transl', function (): void {
    app()->singleton(TranslPullCommand::class, static function (): TranslPullCommand {
        return new class () extends TranslPullCommand {
            public function handle(): int
            {
                return TranslPullCommand::SUCCESS;
            }
        };
    });
    app()->singleton(TranslPushCommand::class, function (): TranslPushCommand {
        return new class () extends TranslPushCommand {
            public readonly bool $used;

            public function handle(): int
            {
                $this->used = true;

                return TranslPushCommand::SUCCESS;
            }
        };
    });

    Artisan::call(TranslSynchCommand::class);

    expect(app(TranslPushCommand::class)->used)->toEqual(true);
});
