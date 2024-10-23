<?php

declare(strict_types=1);

namespace Transl\Commands\Concerns;

use Symfony\Component\Console\Helper\ProgressBar;

/**
 * @mixin \Illuminate\Console\Command
 */
trait UsesProgressBar
{
    protected function createProgressBar(): ProgressBar
    {
        $bar = $this->output->createProgressBar();

        $bar->setEmptyBarCharacter('░'); // light shade character \u2591
        $bar->setProgressCharacter('');
        $bar->setBarCharacter('▓'); // dark shade character \u2593

        /**
         * 10/100 [▓▓▓░░░░░░░░░░░░░░░░░░░░░░░░░]   10%.
         */
        // $bar->setFormat(ProgressBar::FORMAT_NORMAL);

        /**
         * 10/100 [▓▓▓░░░░░░░░░░░░░░░░░░░░░░░░░]   10% 5 secs.
         */
        // $bar->setFormat(ProgressBar::FORMAT_VERBOSE);

        /**
         * 10/100 [▓▓▓░░░░░░░░░░░░░░░░░░░░░░░░░]   10% 5 secs/5 mins, 33 secs.
         */
        $bar->setFormat(ProgressBar::FORMAT_VERY_VERBOSE);

        /**
         * 10/100 [▓▓▓░░░░░░░░░░░░░░░░░░░░░░░░░]   10% 5 secs/5 mins, 33 secs 28.0 MiB.
         */
        // $bar->setFormat(ProgressBar::FORMAT_DEBUG);

        return $bar;
    }
}
