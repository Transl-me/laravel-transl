<?php

declare(strict_types=1);

namespace Transl\Actions\LocalFilesDriver;

use Transl\Support\Helper;
use Transl\Support\TranslationSet;
use Transl\Actions\LocalFilesDriver\AbstractLocalFilesDriverAction;

class SaveTrackedTranslationSetToLocalFilesAction extends AbstractLocalFilesDriverAction
{
    /**
     * Execute the action.
     */
    public function execute(TranslationSet $set): void
    {
        $path = $this->driver->getTrackedTranslationSetPath($this->project, $this->branch, $set);

        if (!$path) {
            return;
        }

        $this->driver->filesystem()->ensureDirectoryExists(dirname($path));

        $this->driver->filesystem()->put($path, Helper::jsonEncode($set->toArray()));
    }
}
