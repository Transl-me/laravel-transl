<?php

declare(strict_types=1);

namespace Transl\Actions\LocalFilesDriver;

use Transl\Support\LocaleFilesystem\FilePath;
use Transl\Actions\LocalFilesDriver\AbstractLocalFilesRetrievalAction;

class CountTranslationSetsFromLocalFilesAction extends AbstractLocalFilesRetrievalAction
{
    /**
     * Execute the action.
     */
    public function execute(): int
    {
        $count = 0;

        foreach ($this->languageDirectories as $languageDirectory) {
            $languageDirectory = FilePath::new($languageDirectory);

            foreach ($this->recursivelyReadDirectory($languageDirectory) as $translationFile) {
                if ($this->shouldIgnoreTranslationFile($translationFile, $languageDirectory)) {
                    continue;
                }

                if (!$this->allowsTranslationFileLocale($translationFile, $languageDirectory)) {
                    continue;
                }

                if (!$this->passesFilter($translationFile, $languageDirectory)) {
                    $this->handleSkipped($translationFile, $languageDirectory);

                    continue;
                }

                $count++;
            }
        }

        return $count;
    }
}
