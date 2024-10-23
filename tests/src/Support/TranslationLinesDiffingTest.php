<?php

declare(strict_types=1);

use Transl\Support\TranslationSet;
use Transl\Support\TranslationLine;
use Illuminate\Contracts\Support\Arrayable;
use Transl\Support\TranslationLinesDiffing;
use Transl\Support\TranslationLineCollection;

beforeEach(function (): void {
    $this->makeLine = static function (
        string $key,
        mixed $value,
        ?array $meta = null,
    ): TranslationLine {
        return TranslationLine::from([
            'key' => $key,
            'value' => $value,
            'meta' => $meta,
        ]);
    };
    $this->makeSet = static function (
        TranslationLineCollection|array $lines,
        string $locale = 'en',
        ?string $group = 'test',
        ?string $namespace = null,
        ?array $meta = null,
    ): TranslationSet {
        return TranslationSet::from([
            'locale' => $locale,
            'group' => $group,
            'namespace' => $namespace,
            'lines' => $lines,
            'meta' => $meta,
        ]);
    };

    $this->addSetLine = function (
        TranslationSet $set,
        string $lineKey,
        string $newValue,
        ?array $newMeta = null,
    ): TranslationSet {
        $line = $this->makeLine->__invoke($lineKey, $newValue, $newMeta);

        return TranslationSet::from([
            ...$set->toArray(),
            'lines' => $set->lines->toBase()->push($line),
        ]);
    };
    $this->updateSetLine = function (
        TranslationSet $set,
        string $lineKey,
        mixed $newValue,
        ?array $newMeta = null,
    ): TranslationSet {
        $line = $set->lines->firstWhere('key', $lineKey);
        $line = $this->makeLine->__invoke($line->key, $newValue, $newMeta ?: $line->meta);

        return TranslationSet::from([
            ...$set->toArray(),
            'lines' => $set->lines->map(static function (TranslationLine $item) use ($line): TranslationLine {
                if ($line->key === $item->key) {
                    return $line;
                }

                return $item;
            }),
        ]);
    };
    $this->removeSetLine = static function (TranslationSet $set, string $lineKey): TranslationSet {
        $line = $set->lines->firstWhere('key', $lineKey);

        return TranslationSet::from([
            ...$set->toArray(),
            'lines' => $set->lines->filter(static function (TranslationLine $item) use ($line): bool {
                return $line->key !== $item->key;
            }),
        ]);
    };
    $this->updateSetLineKey = static function (
        TranslationSet $set,
        string $lineKey,
        string $newKey,
    ): TranslationSet {
        return TranslationSet::from([
            ...$set->toArray(),
            'lines' => $set->lines->map(static function (TranslationLine $item) use ($lineKey, $newKey): TranslationLine {
                if ($lineKey !== $item->key) {
                    return $item;
                }

                return TranslationLine::from([
                    ...$item->toArray(),
                    'key' => $newKey,
                ]);
            }),
        ]);
    };

    $this->tracked = $this->makeSet->__invoke([
        $this->makeLine->__invoke('email', 'Tracked "email" value.'),
        $this->makeLine->__invoke('first_name', 'Tracked "first_name" value.'),
        $this->makeLine->__invoke('last_name', 'Tracked "last_name" value.'),
        $this->makeLine->__invoke('password', 'Tracked "password" value.'),
    ]);
});

describe('Base', function (): void {
    it('implements the `Arrayable` contract', function (): void {
        expect(in_array(Arrayable::class, class_implements(TranslationLinesDiffing::class), true))->toEqual(true);
    });

    it('can be represented as an array', function (): void {
        $current = clone $this->tracked;
        $incoming = clone $this->tracked;

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->toArray())->toEqual([
            'tracked_lines' => $this->tracked->lines->toArray(),
            'current_lines' => $current->lines->toArray(),
            'incoming_lines' => $incoming->lines->toArray(),
        ]);
    });
});

describe('Misc.', function (): void {
    it('can handle lines with empty array values', function (): void {
        $current = $this->updateSetLine->__invoke(clone $this->tracked, 'email', []);
        $incoming = clone $this->tracked;

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual(
            collect($this->tracked->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual(
            collect($incoming->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());
    });
});

/* Values
------------------------------------------------*/

describe('Values | Same', function (): void {
    /* Same | Same
    ------------------------------------------------*/

    test('[tracked | current:same | incoming:same]', function (): void {
        $current = clone $this->tracked;
        $incoming = clone $this->tracked;

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual($this->tracked->lines->toRawTranslationLines());

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());
    });

    /* Same | Changed
    ------------------------------------------------*/

    test('[tracked | current:same | incoming:changed]', function (): void {
        $current = clone $this->tracked;
        $incoming = $this->updateSetLine->__invoke(clone $this->tracked, 'email', 'Incoming "email" value.');

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Incoming "email" value.',
        ]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Incoming "email" value.',
        ]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual(
            collect($this->tracked->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([
            'email' => 'Incoming "email" value.',
        ]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());
    });

    test('[tracked | current:changed | incoming:same]', function (): void {
        $current = $this->updateSetLine->__invoke(clone $this->tracked, 'email', 'Current "email" value.');
        $incoming = clone $this->tracked;

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual(
            collect($this->tracked->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual(
            collect($incoming->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());
    });

    /* Same | Added
    ------------------------------------------------*/

    test('[tracked | current:same | incoming:added]', function (): void {
        $current = clone $this->tracked;
        $incoming = $this->addSetLine->__invoke(clone $this->tracked, 'username', 'Incoming "username" value.');

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([
            'username' => 'Incoming "username" value.',
        ]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual($this->tracked->lines->toRawTranslationLines());

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([
            'username' => 'Incoming "username" value.',
        ]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([
            'username' => 'Incoming "username" value.',
        ]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());
    });

    test('[tracked | current:added | incoming:same]', function (): void {
        $current = $this->addSetLine->__invoke(clone $this->tracked, 'username', 'Current "username" value.');
        $incoming = clone $this->tracked;

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual($this->tracked->lines->toRawTranslationLines());

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual([
            'username' => 'Current "username" value.',
            ...$incoming->lines->toRawTranslationLines(),
        ]);
    });

    /* Same | Removed
    ------------------------------------------------*/

    test('[tracked | current:same | incoming:removed]', function (): void {
        $current = clone $this->tracked;
        $incoming = $this->removeSetLine->__invoke(clone $this->tracked, 'email');

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual(
            collect($this->tracked->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());
    });

    test('[tracked | current:removed | incoming:same]', function (): void {
        $current = $this->removeSetLine->__invoke(clone $this->tracked, 'email');
        $incoming = clone $this->tracked;

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual(
            collect($this->tracked->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual(
            collect($incoming->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual(
            collect($current->lines->toRawTranslationLines())->forget('email')->toArray(),
        );
    });
});

describe('Values | Changed', function (): void {
    /* Changed | Changed
    ------------------------------------------------*/

    test('[tracked | current:changed | incoming:changed]', function (): void {
        $current = $this->updateSetLine->__invoke(clone $this->tracked, 'email', 'Current "email" value.');
        $incoming = $this->updateSetLine->__invoke(clone $this->tracked, 'email', 'Incoming "email" value.');

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Incoming "email" value.',
        ]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Incoming "email" value.',
        ]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual(
            collect($this->tracked->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([
            'email' => 'Incoming "email" value.',
        ]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual(
            collect($incoming->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());
    });

    test('[tracked | current:changed | incoming:changed (same change)]', function (): void {
        $current = $this->updateSetLine->__invoke(clone $this->tracked, 'email', 'Current/Incoming "email" value.');
        $incoming = $this->updateSetLine->__invoke(clone $this->tracked, 'email', 'Current/Incoming "email" value.');

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());
    });

    /* Changed | Added
    ------------------------------------------------*/

    test('[tracked | current:changed | incoming:added]', function (): void {
        $current = $this->updateSetLine->__invoke(clone $this->tracked, 'email', 'Current "email" value.');
        $incoming = $this->addSetLine->__invoke(clone $this->tracked, 'username', 'Incoming "username" value.');

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
            'username' => 'Incoming "username" value.',
        ]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual(
            collect($this->tracked->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([
            'username' => 'Incoming "username" value.',
        ]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([
            'username' => 'Incoming "username" value.',
        ]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual([
            ...collect($incoming->lines->toRawTranslationLines())->forget('email')->toArray(),
            'username' => 'Incoming "username" value.',
        ]);

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual([
            ...$current->lines->toRawTranslationLines(),
            'username' => 'Incoming "username" value.',
        ]);

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual([
            ...$current->lines->toRawTranslationLines(),
            'username' => 'Incoming "username" value.',
        ]);

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());
    });

    test('[tracked | current:added | incoming:changed]', function (): void {
        $current = $this->addSetLine->__invoke(clone $this->tracked, 'username', 'Current "username" value.');
        $incoming = $this->updateSetLine->__invoke(clone $this->tracked, 'email', 'Incoming "email" value.');

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Incoming "email" value.',
        ]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Incoming "email" value.',
        ]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual(
            collect($this->tracked->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([
            'email' => 'Incoming "email" value.',
        ]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual([
            ...$current->lines->toRawTranslationLines(),
            'email' => 'Incoming "email" value.',
        ]);

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual([
            'username' => 'Current "username" value.',
            ...$incoming->lines->toRawTranslationLines(),
        ]);
    });

    /* Changed | Removed
    ------------------------------------------------*/

    test('[tracked | current:changed | incoming:removed]', function (): void {
        $current = $this->updateSetLine->__invoke(clone $this->tracked, 'email', 'Current "email" value.');
        $incoming = $this->removeSetLine->__invoke(clone $this->tracked, 'email');

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual(
            collect($this->tracked->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual(
            collect($incoming->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());
    });

    test('[tracked | current:removed | incoming:changed]', function (): void {
        $current = $this->removeSetLine->__invoke(clone $this->tracked, 'email');
        $incoming = $this->updateSetLine->__invoke(clone $this->tracked, 'email', 'Incoming "email" value.');

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Incoming "email" value.',
        ]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual(
            collect($this->tracked->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Incoming "email" value.',
        ]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual(
            collect($incoming->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());
    });
});

describe('Values | Added', function (): void {
    /* Added | Added
    ------------------------------------------------*/

    test('[tracked | current:added | incoming:added]', function (): void {
        $current = $this->addSetLine->__invoke(clone $this->tracked, 'username', 'Current "username" value.');
        $incoming = $this->addSetLine->__invoke(clone $this->tracked, 'username_bis', 'Incoming "username_bis" value.');

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([
            'username_bis' => 'Incoming "username_bis" value.',
        ]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual($this->tracked->lines->toRawTranslationLines());

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([
            'username_bis' => 'Incoming "username_bis" value.',
        ]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([
            'username_bis' => 'Incoming "username_bis" value.',
        ]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual([
            ...$current->lines->toRawTranslationLines(),
            'username_bis' => 'Incoming "username_bis" value.',
        ]);

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual([
            ...$current->lines->toRawTranslationLines(),
            'username_bis' => 'Incoming "username_bis" value.',
        ]);

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual([
            'username' => 'Current "username" value.',
            ...$incoming->lines->toRawTranslationLines(),
        ]);
    });

    /* Added | Removed
    ------------------------------------------------*/

    test('[tracked | current:added | incoming:removed]', function (): void {
        $current = $this->addSetLine->__invoke(clone $this->tracked, 'username', 'Current "username" value.');
        $incoming = $this->removeSetLine->__invoke(clone $this->tracked, 'email');

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual(
            collect($this->tracked->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual(
            collect($current->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual([
            'username' => 'Current "username" value.',
            ...$incoming->lines->toRawTranslationLines(),
        ]);
    });

    test('[tracked | current:removed | incoming:added]', function (): void {
        $current = $this->removeSetLine->__invoke(clone $this->tracked, 'email');
        $incoming = $this->addSetLine->__invoke(clone $this->tracked, 'username', 'Incoming "username" value.');

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
            'username' => 'Incoming "username" value.',
        ]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual(
            collect($this->tracked->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
            'username' => 'Incoming "username" value.',
        ]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([
            'username' => 'Incoming "username" value.',
        ]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual(
            collect($incoming->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual([
            ...$current->lines->toRawTranslationLines(),
            'username' => 'Incoming "username" value.',
        ]);

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual([
            ...$current->lines->toRawTranslationLines(),
            'username' => 'Incoming "username" value.',
        ]);

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());
    });
});

describe('Values | Removed', function (): void {
    /* Removed | Removed
    ------------------------------------------------*/

    test('[tracked | current:removed | incoming:removed]', function (): void {
        $current = $this->removeSetLine->__invoke(clone $this->tracked, 'email');
        $incoming = $this->removeSetLine->__invoke(clone $this->tracked, 'email');

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual(
            collect($this->tracked->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual($current->lines->toRawTranslationLines());

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());
    });
});

/* Keys
------------------------------------------------*/

describe('Keys', function (): void {
    it('treats key updates as 1 deletion & 1 addition', function (): void {
        $current = clone $this->tracked;
        $incoming = $this->updateSetLineKey->__invoke(clone $this->tracked, 'email', 'e-mail');

        $diff = TranslationLinesDiffing::new(
            trackedLines: $this->tracked->lines,
            currentLines: $current->lines,
            incomingLines: $incoming->lines,
        );

        expect($diff->changedLines()->toRawTranslationLines())->toEqual([
            'e-mail' => 'Tracked "email" value.',
        ]);

        expect($diff->updatedLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->sameLines()->toRawTranslationLines())->toEqual(
            collect($this->tracked->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->addedLines()->toRawTranslationLines())->toEqual([
            'e-mail' => 'Tracked "email" value.',
        ]);

        expect($diff->removedLines()->toRawTranslationLines())->toEqual([
            'email' => 'Tracked "email" value.',
        ]);

        expect($diff->conflictingLines()->toRawTranslationLines())->toEqual([]);

        expect($diff->nonConflictingLines()->toRawTranslationLines())->toEqual([
            'e-mail' => 'Tracked "email" value.',
        ]);

        expect($diff->safeLines()->toRawTranslationLines())->toEqual(
            collect($incoming->lines->toRawTranslationLines())->forget('email')->toArray(),
        );

        expect($diff->mergeableLines()->toRawTranslationLines())->toEqual([
            ...collect($current->lines->toRawTranslationLines())->forget('email')->toArray(),
            'e-mail' => 'Tracked "email" value.',
        ]);

        expect($diff->favorCurrentLines()->toRawTranslationLines())->toEqual([
            ...$current->lines->toRawTranslationLines(),
            'e-mail' => 'Tracked "email" value.',
        ]);

        expect($diff->favorIncomingLines()->toRawTranslationLines())->toEqual($incoming->lines->toRawTranslationLines());
    });
});
