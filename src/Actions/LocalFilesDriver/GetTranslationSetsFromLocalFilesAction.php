<?php

declare(strict_types=1);

namespace Transl\Actions\LocalFilesDriver;

use Transl\Support\TranslationSet;
use Transl\Support\LocaleFilesystem\FilePath;
use Transl\Actions\LocalFilesDriver\AbstractLocalFilesRetrievalAction;

class GetTranslationSetsFromLocalFilesAction extends AbstractLocalFilesRetrievalAction
{
    /**
     * Execute the action.
     *
     * @return iterable<TranslationSet>
     */
    public function execute(): iterable
    {
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

                yield $this->makeTranslationSetFromTranslationFile($translationFile, $languageDirectory);
            }
        }
    }
}
