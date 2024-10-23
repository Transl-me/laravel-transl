<?php

declare(strict_types=1);

namespace Transl\Config\Enums;

enum BranchingConflictResolutionEnum: string
{
    /**
     * Merge incoming and current translation lines
     * with current lines taking precedence over
     * updated and removed incoming lines.
     *
     * Essentially, bypass any conlicts in favor
     * of current translation lines.
     */
    case ACCEPT_CURRENT = 'accept_current';

    /**
     * Merge incoming and current translation lines
     * with incoming lines taking precedence over
     * updated and removed current lines.
     *
     * Essentially, bypass any conlicts in favor
     * of incoming translation lines.
     */
    case ACCEPT_INCOMING = 'accept_incoming';

    /**
     * Merge incoming, non conflicting (updates & removals),
     * translation lines into current translation lines.
     *
     * Throws an exception if there are conflicting
     * translation lines between incoming and current.
     */
    case MERGE_BUT_THROW = 'merge_but_throw';

    /**
     * Merge incoming, non conflicting (updates & removals),
     * translation lines into current translation lines.
     *
     * Silently discards conflicting translation lines
     * between incoming and current.
     */
    case MERGE_AND_IGNORE = 'merge_and_ignore';

    /**
     * Throws an exception if there are conflicting
     * translation lines between incoming and current
     * without merging any lines.
     */
    case THROW = 'throw';

    /**
     * Silently discards conflicting translation lines
     * between incoming and current without merging
     * any lines.
     */
    case IGNORE = 'ignore';
}
