<?php

declare(strict_types=1);

namespace Transl\Support\Reports\MissingTranslationKeys;

use Transl\Support\Branch;
use Transl\Config\ProjectConfiguration;
use Illuminate\Contracts\Support\Arrayable;
use Transl\Support\Reports\MissingTranslationKeys\MissingTranslationKey;

/**
 * @implements Arrayable<string, mixed>
 * @phpstan-consistent-constructor
 */
class MissingTranslationKeyReport implements Arrayable
{
    public function __construct(
        public readonly ProjectConfiguration $project,
        public readonly Branch $branch,
        public readonly MissingTranslationKey $key,
    ) {
    }

    public static function new(ProjectConfiguration $project, Branch $branch, MissingTranslationKey $key): static
    {
        return new static($project, $branch, $key);
    }

    public function group(): string
    {
        return "{$this->project->auth_key}:{$this->branch->name}";
    }

    public function id(): string
    {
        return "{$this->group()}:{$this->key->id()}";
    }

    /**
     * Get the instance as an array.
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key->toArray(),
            'project' => $this->project->toArray(),
            'branch' => $this->branch->toArray(),
        ];
    }
}
