<?php

declare(strict_types=1);

namespace Transl\Actions\LocalFilesDriver;

use Transl\Actions\LocalFilesDriver\AbstractLocalFilesDriverAction;
use Transl\Exceptions\LocalFilesDriver\MissingRequiredTranslationSetGroup;

class GetTranslationContentsFromLocalFilesAction extends AbstractLocalFilesDriverAction
{
    /**
     * Execute the action.
     */
    public function execute(string $locale, ?string $group, ?string $namespace): array
    {
        $loader = $this->driver->translationLoader();

        if (is_null($group) && is_null($namespace)) {
            $group = '*';
            $namespace = '*';
        }

        if (!$group) {
            throw MissingRequiredTranslationSetGroup::make($this->driver::class, 'getTranslationContents');
        }

        return $loader->load($locale, $group, $namespace);
    }
}
