<?php

declare(strict_types=1);

namespace Transl\Actions\LocalFilesDriver;

use Transl\Support\TranslationSet;
use Transl\Actions\LocalFilesDriver\AbstractLocalFilesDriverAction;

class GetTrackedTranslationSetFromLocalFilesAction extends AbstractLocalFilesDriverAction
{
    /**
     * Execute the action.
     */
    public function execute(TranslationSet $set): ?TranslationSet
    {
        $path = $this->driver->getTrackedTranslationSetPath($this->project, $this->branch, $set);
        $filesystem = $this->driver->filesystem();

        if (!$path || !$filesystem->exists($path)) {
            return null;
        }

        $raw = $filesystem->json($path);

        return TranslationSet::from($raw);
    }
}
