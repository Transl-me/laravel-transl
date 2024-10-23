<?php

declare(strict_types=1);

// use Illuminate\Support\Facades\Artisan;
use Transl\Commands\TranslAnalyseCommand;

it('works', function (): void {
    expect(
        $this->withoutMockingConsoleOutput()->artisan('transl:analyse --branch=yolo'),
    )->toEqual(TranslAnalyseCommand::SUCCESS);
    // expect(Artisan::output())->toMatchConsoleOutput();
});

it('works (verbose)', function (): void {
    expect(
        $this->withoutMockingConsoleOutput()->artisan('transl:analyse --branch=yolo -v'),
    )->toEqual(TranslAnalyseCommand::SUCCESS);
    // expect(Artisan::output())->toMatchConsoleOutput();
});

it('works (very verbose)', function (): void {
    expect(
        $this->withoutMockingConsoleOutput()->artisan('transl:analyse --branch=yolo -vv'),
    )->toEqual(TranslAnalyseCommand::SUCCESS);
    // expect(Artisan::output())->toMatchConsoleOutput();
});

it('works (debug)', function (): void {
    expect(
        $this->withoutMockingConsoleOutput()->artisan('transl:analyse --branch=yolo -vvv'),
    )->toEqual(TranslAnalyseCommand::SUCCESS);
    // expect(Artisan::output())->toMatchConsoleOutput();
});
