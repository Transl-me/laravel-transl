<?php

declare(strict_types=1);

use Transl\Support\Git;
use Transl\Facades\Transl;
use Transl\Support\Branch;
use Transl\Config\Configuration;
use Illuminate\Translation\Translator;
use Transl\Actions\Reports\ReportMissingTranslationKeysAction;
use Transl\Actions\Reports\SendMissingTranslationKeyReportAction;
use Transl\Exceptions\ProjectConfiguration\MultipleProjectsFound;
use Transl\Exceptions\ProjectConfiguration\CouldNotDetermineProject;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Transl\Exceptions\Report\MissingTranslationKeys\CouldNotBuildReport;
use Transl\Support\Reports\MissingTranslationKeys\MissingTranslationKey;
use Transl\Support\Reports\MissingTranslationKeys\MissingTranslationKeys;
use Transl\Support\Reports\MissingTranslationKeys\MissingTranslationKeyReport;

beforeEach(function (): void {
    Process::fake([
        Git::getCurrentBranchNameCommand() => '__feature/new__',
    ]);

    Process::fake([
        Git::getDefaultConfiguredBranchNameCommand() => '__default__',
    ]);

    $this->translConfig = config('transl');
});

afterEach(function (): void {
    config()->set('transl', $this->translConfig);

    Configuration::refreshInstance(config('transl'));
});

/* Successes
------------------------------------------------*/

describe('queueing with all params given', function (): void {
    it('can add a raw missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true, $project, $branch);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });

    it('can register a missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->register($missingKey, $project, $branch);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });

    it('can queue a missing translation key report', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->queue($missingKeyReport);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });
});

describe('queueing with a given project "auth_key"', function (): void {
    it('can add a raw missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true, $project->auth_key, $branch);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });

    it('can register a missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->register($missingKey, $project->auth_key, $branch);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });
});

describe('queueing with a given project "name"', function (): void {
    it('can add a raw missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true, $project->name, $branch);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });

    it('can register a missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->register($missingKey, $project->name, $branch);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });
});

describe('queueing with a branch given as a string', function (): void {
    it('can add a raw missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asProvided('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true, $project, $branch->name);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });

    it('can register a missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asProvided('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->register($missingKey, $project, $branch->name);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });
});

describe('queueing with a project and branch given as a strings', function (): void {
    it('can add a raw missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asProvided('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true, $project->auth_key, $branch->name);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });

    it('can register a missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asProvided('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->register($missingKey, $project->auth_key, $branch->name);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });

    it('can queue a missing translation key report', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->queue($missingKeyReport);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });
});

describe('queueing without a given project', function (): void {
    it('can add a raw missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true, null, $branch);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });

    it('can register a missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->register($missingKey, null, $branch);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });
});

describe('queueing without a given branch (project allows mirroring)', function (): void {
    it('can add a raw missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent(Git::currentBranchName());

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true, $project, null);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });

    it('can register a missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent(Git::currentBranchName());

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->register($missingKey, $project, null);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });
});

describe('queueing without a given branch (project disallows mirroring but provides default)', function (): void {
    beforeEach(function (): void {
        config()->set('transl.defaults.project_options.branching.mirror_current_branch', false);

        Configuration::refreshInstance(config('transl'));
    });

    it('can add a raw missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asDefault($project->options->branching->default_branch_name);

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true, $project, null);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });

    it('can register a missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asDefault($project->options->branching->default_branch_name);

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->register($missingKey, $project, null);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });
});

describe('queueing without a given branch (project disallows mirroring & does not provide default)', function (): void {
    beforeEach(function (): void {
        config()->set('transl.defaults.project_options.branching.mirror_current_branch', false);
        config()->set('transl.defaults.project_options.branching.default_branch_name', null);

        Configuration::refreshInstance(config('transl'));
    });

    it('can add a raw missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asDefault(Git::defaultConfiguredBranchName());

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true, $project, null);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });

    it('can register a missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asDefault(Git::defaultConfiguredBranchName());

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->register($missingKey, $project, null);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });
});

describe('queueing without a given branch (uses fallback when necessary)', function (): void {
    beforeEach(function (): void {
        config()->set('transl.defaults.project_options.branching.mirror_current_branch', false);
        config()->set('transl.defaults.project_options.branching.default_branch_name', null);

        Configuration::refreshInstance(config('transl'));
    });

    it('can add a raw missing translation key', function (): void {
        Process::fake([
            Git::getDefaultConfiguredBranchNameCommand() => '',
        ]);

        $project = Transl::config()->projects()->first();
        $branch = Branch::asFallback(Transl::FALLBACK_BRANCH_NAME);

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true, $project, null);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });

    it('can register a missing translation key', function (): void {
        Process::fake([
            Git::getDefaultConfiguredBranchNameCommand() => '',
        ]);

        $project = Transl::config()->projects()->first();
        $branch = Branch::asFallback(Transl::FALLBACK_BRANCH_NAME);

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->register($missingKey, $project, null);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });
});

describe('queueing without a given project or branch', function (): void {
    it('can add a raw missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent(Git::currentBranchName());

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true, null, null);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });

    it('can register a missing translation key', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent(Git::currentBranchName());

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->register($missingKey, null, null);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });
});

describe('misc.', function (): void {
    it("is registered on Laravel's container", function (): void {
        expect(app()->bound(MissingTranslationKeys::class))->toEqual(true);
    });

    it("is registered on Laravel's container as a singleton", function (): void {
        (new MissingTranslationKeys())->add('auth.password', [], 'en', true);
        (new MissingTranslationKeys())->add('auth.password', [], 'es', true);
        (new MissingTranslationKeys())->add('auth.password', [], 'fr', true);

        $missingKeys = new MissingTranslationKeys();

        expect(count($missingKeys->queued()))->toEqual(0);

        $missingKeys = app(MissingTranslationKeys::class);

        expect(count($missingKeys->queued()))->toEqual(0);

        app(MissingTranslationKeys::class)->add('auth.password', [], 'en', true);
        app(MissingTranslationKeys::class)->add('auth.password', [], 'es', true);
        app(MissingTranslationKeys::class)->add('auth.password', [], 'fr', true);

        $missingKeys = app(MissingTranslationKeys::class);

        expect(count($missingKeys->queued()))->toEqual(3);

        $missingKeys->flushQueue();

        expect(app(MissingTranslationKeys::class)->queued())->toEqual([]);
    });

    it('can set the entire queue', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->setQueue([$missingKeyReport]);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });

    it('can flush the entire queue', function (): void {
        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->setQueue([$missingKeyReport]);

        $missingKeys->flushQueue();

        expect($missingKeys->queued())->toEqual([]);
    });

    it('does not allows for duplicates when adding raw missing translation keys', function (): void {
        $project = Transl::config()->projects()->first();
        $branch1 = Branch::asCurrent('yolo');
        $branch2 = Branch::asFallback('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport1 = MissingTranslationKeyReport::new($project, $branch1, $missingKey);
        $missingKeyReport2 = MissingTranslationKeyReport::new($project, $branch2, $missingKey);

        $missingKeys = (new MissingTranslationKeys())
            ->add('auth.password', [], 'en', true, $project, $branch1)
            ->add('auth.password', [], 'en', true, $project, $branch1)
            ->add('auth.password', [], 'en', true, $project, $branch2);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport1->id() => $missingKeyReport1,
            $missingKeyReport2->id() => $missingKeyReport2,
        ]);
    });

    it('does not allows for duplicates when registering missing translation keys', function (): void {
        $project = Transl::config()->projects()->first();
        $branch1 = Branch::asCurrent('yolo');
        $branch2 = Branch::asFallback('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport1 = MissingTranslationKeyReport::new($project, $branch1, $missingKey);
        $missingKeyReport2 = MissingTranslationKeyReport::new($project, $branch2, $missingKey);

        $missingKeys = (new MissingTranslationKeys())
            ->register($missingKey, $project, $branch1)
            ->register($missingKey, $project, $branch1)
            ->register($missingKey, $project, $branch2);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport1->id() => $missingKeyReport1,
            $missingKeyReport2->id() => $missingKeyReport2,
        ]);
    });

    it('does not allows for duplicates when queueing missing translation key reports', function (): void {
        $project = Transl::config()->projects()->first();
        $branch1 = Branch::asCurrent('yolo');
        $branch2 = Branch::asFallback('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport1 = MissingTranslationKeyReport::new($project, $branch1, $missingKey);
        $missingKeyReport1Bis = MissingTranslationKeyReport::new($project, $branch1, $missingKey);
        $missingKeyReport2 = MissingTranslationKeyReport::new($project, $branch2, $missingKey);

        $missingKeys = (new MissingTranslationKeys())
            ->queue($missingKeyReport1)
            ->queue($missingKeyReport1Bis)
            ->queue($missingKeyReport2);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport1->id() => $missingKeyReport1,
            $missingKeyReport2->id() => $missingKeyReport2,
        ]);
    });

    it('does not allows for duplicates when setting the entire queue', function (): void {
        $project = Transl::config()->projects()->first();
        $branch1 = Branch::asCurrent('yolo');
        $branch2 = Branch::asFallback('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);
        $missingKeyReport1 = MissingTranslationKeyReport::new($project, $branch1, $missingKey);
        $missingKeyReport1Bis = MissingTranslationKeyReport::new($project, $branch1, $missingKey);
        $missingKeyReport2 = MissingTranslationKeyReport::new($project, $branch2, $missingKey);

        $missingKeys = (new MissingTranslationKeys())->setQueue([
            $missingKeyReport1,
            $missingKeyReport1Bis,
            $missingKeyReport2,
        ]);

        expect($missingKeys->queued())->toEqual([
            $missingKeyReport1->id() => $missingKeyReport1,
            $missingKeyReport2->id() => $missingKeyReport2,
        ]);
    });
});

describe('reporting', function (): void {
    it('uses `SendMissingTranslationKeyReportAction` to report back to Transl', function (): void {
        app()->singleton(SendMissingTranslationKeyReportAction::class, function (): SendMissingTranslationKeyReportAction {
            return new class () extends SendMissingTranslationKeyReportAction {
                public readonly bool $used;

                public function execute(array $reports): void
                {
                    $this->used = true;
                }
            };
        });

        (new MissingTranslationKeys())->add('auth.password', [], 'en', true)->report();

        expect(app(SendMissingTranslationKeyReportAction::class)->used)->toEqual(true);
    });

    it('does not report back to Transl if the queue is empty', function (): void {
        app()->singleton(SendMissingTranslationKeyReportAction::class, function (): SendMissingTranslationKeyReportAction {
            return new class () extends SendMissingTranslationKeyReportAction {
                public bool $used = false;

                public function execute(array $reports): void
                {
                    $this->used = true;
                }
            };
        });

        (new MissingTranslationKeys())->report();

        expect(app(SendMissingTranslationKeyReportAction::class)->used)->toEqual(false);
    });

    it('flushes the queue after successfully reporting back to Transl', function (): void {
        app()->singleton(SendMissingTranslationKeyReportAction::class, function (): SendMissingTranslationKeyReportAction {
            return new class () extends SendMissingTranslationKeyReportAction {
                public readonly bool $used;

                public function execute(array $reports): void
                {
                    $this->used = true;
                }
            };
        });

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true);

        expect(empty($missingKeys->queued()))->toEqual(false);

        $missingKeys->report();

        expect($missingKeys->queued())->toEqual([]);
    });

    it('flushes the queue after failed attempt at reporting back to Transl', function (): void {
        app()->singleton(SendMissingTranslationKeyReportAction::class, static function (): SendMissingTranslationKeyReportAction {
            return new class () extends SendMissingTranslationKeyReportAction {
                public function execute(array $reports): void
                {
                    throw new Exception('Oops');
                }
            };
        });

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true);

        expect(empty($missingKeys->queued()))->toEqual(false);

        expect(static fn () => $missingKeys->report())->toThrow(Exception::class);

        expect($missingKeys->queued())->toEqual([]);
    });
});

describe('catching missing translation keys (Lang::handleMissingKeysUsing)', function (): void {
    it('does nothing when the feature is disabled', function (): void {
        config()->set('transl.reporting.should_report_missing_translation_keys', false);

        Configuration::refreshInstance(config('transl'));

        __('nope');

        expect(app(MissingTranslationKeys::class)->queued())->toEqual([]);

        config()->set('transl.reporting.should_report_missing_translation_keys', true);

        Configuration::refreshInstance(config('transl'));
    });

    it('does nothing when no valid handler is provided', function (): void {
        config()->set('transl.reporting.report_missing_translation_keys_using', '');

        Configuration::refreshInstance(config('transl'));

        __('nope');

        expect(app(MissingTranslationKeys::class)->queued())->toEqual([]);

        config()->set('transl.reporting.report_missing_translation_keys_using', ReportMissingTranslationKeysAction::class);

        Configuration::refreshInstance(config('transl'));
    });

    it('does nothing if the Translator does not support the feature', function (): void {
        app()->singleton('translator', static function (): TranslatorContract {
            return new class () implements TranslatorContract {
                public function get($key, array $replace = [], $locale = null): void
                {
                    //
                }

                public function choice($key, $number, array $replace = [], $locale = null): void
                {
                    //
                }

                public function getLocale(): void
                {
                    //
                }

                public function setLocale($locale): void
                {
                    //
                }

                public function addNamespace($namespace, $hint): void
                {
                    //
                }
            };
        });

        __('nope');

        expect(app(MissingTranslationKeys::class)->queued())->toEqual([]);
    });

    it('is able to catch missing translation keys if the Translator supports the feature', function (): void {
        app()->singleton('translator', static function (): TranslatorContract {
            return new class () implements TranslatorContract {
                protected Closure $missingTranslationKeyCallback;

                public function get($key, array $replace = [], $locale = null): void
                {
                    $this->handleMissingTranslationKey($key, $replace, $locale, false);
                }

                public function choice($key, $number, array $replace = [], $locale = null): void
                {
                    //
                }

                public function getLocale(): void
                {
                    //
                }

                public function setLocale($locale): void
                {
                    //
                }

                public function addNamespace($namespace, $hint): void
                {
                    //
                }

                public function handleMissingKeysUsing(?callable $callback)
                {
                    $this->missingTranslationKeyCallback = $callback;

                    return $this;
                }

                protected function handleMissingTranslationKey($key, $replace, $locale, $fallback): void
                {
                    ($this->missingTranslationKeyCallback)($key, $replace, $locale, $fallback);
                }
            };
        });

        __('nope', ['yep' => 'yolo'], 'ht');

        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent(Git::currentBranchName());

        $missingKey = MissingTranslationKey::new('nope', ['yep' => 'yolo'], 'ht', false);
        $missingKeyReport = MissingTranslationKeyReport::new($project, $branch, $missingKey);

        expect(app(MissingTranslationKeys::class)->queued())->toEqual([
            $missingKeyReport->id() => $missingKeyReport,
        ]);
    });

    it('uses `ReportMissingTranslationKeysAction` to report missing translation keys', function (): void {
        app()->singleton(ReportMissingTranslationKeysAction::class, function (): ReportMissingTranslationKeysAction {
            return new class () extends ReportMissingTranslationKeysAction {
                public readonly bool $used;

                public function execute(string $key, array $replacements, string $locale, bool $fallback): string
                {
                    $this->used = true;

                    return '';
                }
            };
        });

        __('nope');

        expect(app(ReportMissingTranslationKeysAction::class)->used)->toEqual(true);
    });

    it('correctly catches missing translation keys', function (): void {
        __('auth.password');
        __('nope');
        __('nope', ['yo' => 'yolo'], 'ht');

        $project = Transl::config()->projects()->first();
        $branch = Branch::asCurrent(Git::currentBranchName());

        $missingKey1 = MissingTranslationKey::new('nope', [], 'en', true);
        $missingKey2 = MissingTranslationKey::new('nope', ['yo' => 'yolo'], 'ht', true);
        $missingKeyReport1 = MissingTranslationKeyReport::new($project, $branch, $missingKey1);
        $missingKeyReport2 = MissingTranslationKeyReport::new($project, $branch, $missingKey2);

        expect(app(MissingTranslationKeys::class)->queued())->toEqual([
            $missingKeyReport1->id() => $missingKeyReport1,
            $missingKeyReport2->id() => $missingKeyReport2,
        ]);
    });

    it('returns the missing translation key as usual even after correctly catching it', function (): void {
        expect(__('nope'))->toEqual('nope');

        expect(empty(app(MissingTranslationKeys::class)->queued()))->toEqual(false);
    });

    it('uses the configured handler to report missing translation keys', function (): void {
        $class = new class () {
            public readonly bool $used;

            public function execute(): void
            {
                $this->used = true;
            }
        };

        app()->singleton($class::class);

        config()->set('transl.reporting.report_missing_translation_keys_using', $class::class);

        Configuration::refreshInstance(config('transl'));

        __('nope');

        expect(app($class::class)->used)->toEqual(true);

        config()->set('transl.reporting.report_missing_translation_keys_using', ReportMissingTranslationKeysAction::class);

        Configuration::refreshInstance(config('transl'));
    });

    it("is able to use the configured handler's \"__invoke\" method to report missing translation keys", function (): void {
        $class = new class () {
            public readonly bool $used;

            public function __invoke(): void
            {
                $this->used = true;
            }
        };

        app()->singleton($class::class);

        config()->set('transl.reporting.report_missing_translation_keys_using', $class::class);

        Configuration::refreshInstance(config('transl'));

        __('nope');

        expect(app($class::class)->used)->toEqual(true);

        config()->set('transl.reporting.report_missing_translation_keys_using', ReportMissingTranslationKeysAction::class);

        Configuration::refreshInstance(config('transl'));
    });

    it("favors the configured handler's \"execute\" method over \"__invoke\" to report missing translation keys", function (): void {
        $class = new class () {
            public readonly bool $invoked;
            public readonly bool $executed;

            public function execute(): void
            {
                $this->executed = true;
            }

            public function __invoke(): void
            {
                $this->invoked = true;
            }
        };

        app()->singleton($class::class);

        config()->set('transl.reporting.report_missing_translation_keys_using', $class::class);

        Configuration::refreshInstance(config('transl'));

        __('nope');

        expect(app($class::class)->executed)->toEqual(true);
        expect(static fn () => app($class::class)->invoked)->toThrow('$invoked must not be accessed before initialization');

        config()->set('transl.reporting.report_missing_translation_keys_using', ReportMissingTranslationKeysAction::class);

        Configuration::refreshInstance(config('transl'));
    });
});

/* Failures (throwing)
------------------------------------------------*/

describe('fails queueing with a given unknown project "auth_key" or "name"', function (): void {
    it('cannot add a raw missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        expect(
            static fn () => (new MissingTranslationKeys())->add('auth.password', [], 'en', true, 'nope', $branch),
        )->toThrow(CouldNotDetermineProject::class);
    });

    it('cannot register a missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);

        expect(
            static fn () => (new MissingTranslationKeys())->register($missingKey, 'nope', $branch),
        )->toThrow(CouldNotDetermineProject::class);
    });
});

describe('fails queueing with a given duplicate project "auth_key" or "name"', function (): void {
    beforeEach(function (): void {
        config()->set('transl.projects', [
            [
                'auth_key' => 'duplicate_auth_key',
                'name' => 'first_name',
            ],
            [
                'auth_key' => 'duplicate_auth_key',
                'name' => 'second_name',
            ],
        ]);

        Configuration::refreshInstance(config('transl'));
    });

    it('cannot add a raw missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        expect(
            static fn () => (new MissingTranslationKeys())->add('auth.password', [], 'en', true, 'duplicate_auth_key', $branch),
        )->toThrow(MultipleProjectsFound::class);
    });

    it('cannot register a missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);

        expect(
            static fn () => (new MissingTranslationKeys())->register($missingKey, 'duplicate_auth_key', $branch),
        )->toThrow(MultipleProjectsFound::class);
    });
});

describe('fails queueing when no project can be guessed', function (): void {
    beforeEach(function (): void {
        config()->set('transl.projects', []);

        Configuration::refreshInstance(config('transl'));
    });

    it('cannot add a raw missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        expect(
            static fn () => (new MissingTranslationKeys())->add('auth.password', [], 'en', true, null, $branch),
        )->toThrow(CouldNotBuildReport::class);
    });

    it('cannot register a missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);

        expect(
            static fn () => (new MissingTranslationKeys())->register($missingKey, null, $branch),
        )->toThrow(CouldNotBuildReport::class);
    });
});

describe('fails queueing when no project can be guessed out of duplicate projects', function (): void {
    beforeEach(function (): void {
        config()->set('transl.projects', [
            [
                'auth_key' => 'duplicate_auth_key',
                'name' => 'first_name',
            ],
            [
                'auth_key' => 'duplicate_auth_key',
                'name' => 'second_name',
            ],
        ]);

        Configuration::refreshInstance(config('transl'));
    });

    it('cannot add a raw missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        expect(
            static fn () => (new MissingTranslationKeys())->add('auth.password', [], 'en', true, null, $branch),
        )->toThrow(MultipleProjectsFound::class);
    });

    it('cannot register a missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);

        expect(
            static fn () => (new MissingTranslationKeys())->register($missingKey, null, $branch),
        )->toThrow(MultipleProjectsFound::class);
    });
});

describe('fails catching missing translation keys (Lang::handleMissingKeysUsing)', function (): void {
    it('is able to throw back thrown exceptions', function (): void {
        app()->singleton('translator', static function (): TranslatorContract {
            return new class (app('translation.loader'), 'jp') extends Translator {
                public function handleMissingKeysUsing(?callable $callback): void
                {
                    throw new Exception('Oops');
                }
            };
        });

        expect(static fn () => __('nope'))->toThrow(Exception::class);
    });
});

/* Failures (silent)
------------------------------------------------*/

describe('silently fails queueing with a given unknown project "auth_key" or "name"', function (): void {
    beforeEach(function (): void {
        config()->set('transl.reporting.silently_discard_exceptions', true);

        Configuration::refreshInstance(config('transl'));
    });

    it('cannot add a raw missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true, 'nope', $branch);

        expect($missingKeys->queued())->toEqual([]);
    });

    it('cannot register a missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);

        $missingKeys = (new MissingTranslationKeys())->register($missingKey, 'nope', $branch);

        expect($missingKeys->queued())->toEqual([]);
    });
});

describe('silently fails queueing with a given duplicate project "auth_key" or "name"', function (): void {
    beforeEach(function (): void {
        config()->set('transl.reporting.silently_discard_exceptions', true);

        Configuration::refreshInstance(config('transl'));

        config()->set('transl.projects', [
            [
                'auth_key' => 'duplicate_auth_key',
                'name' => 'first_name',
            ],
            [
                'auth_key' => 'duplicate_auth_key',
                'name' => 'second_name',
            ],
        ]);

        Configuration::refreshInstance(config('transl'));
    });

    it('cannot add a raw missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true, 'duplicate_auth_key', $branch);

        expect($missingKeys->queued())->toEqual([]);
    });

    it('cannot register a missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);

        $missingKeys = (new MissingTranslationKeys())->register($missingKey, 'duplicate_auth_key', $branch);

        expect($missingKeys->queued())->toEqual([]);
    });
});

describe('silently fails queueing when no project can be guessed', function (): void {
    beforeEach(function (): void {
        config()->set('transl.reporting.silently_discard_exceptions', true);
        config()->set('transl.projects', []);

        Configuration::refreshInstance(config('transl'));
    });

    it('cannot add a raw missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true, null, $branch);

        expect($missingKeys->queued())->toEqual([]);
    });

    it('cannot register a missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);

        $missingKeys = (new MissingTranslationKeys())->register($missingKey, null, $branch);

        expect($missingKeys->queued())->toEqual([]);
    });
});

describe('silently fails queueing when no project can be guessed out of duplicate projects', function (): void {
    beforeEach(function (): void {
        config()->set('transl.reporting.silently_discard_exceptions', true);

        config()->set('transl.projects', [
            [
                'auth_key' => 'duplicate_auth_key',
                'name' => 'first_name',
            ],
            [
                'auth_key' => 'duplicate_auth_key',
                'name' => 'second_name',
            ],
        ]);

        Configuration::refreshInstance(config('transl'));
    });

    it('cannot add a raw missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        $missingKeys = (new MissingTranslationKeys())->add('auth.password', [], 'en', true, null, $branch);

        expect($missingKeys->queued())->toEqual([]);
    });

    it('cannot register a missing translation key', function (): void {
        $branch = Branch::asCurrent('yolo');

        $missingKey = MissingTranslationKey::new('auth.password', [], 'en', true);

        $missingKeys = (new MissingTranslationKeys())->register($missingKey, null, $branch);

        expect($missingKeys->queued())->toEqual([]);
    });
});

describe('silently fails catching missing translation keys (Lang::handleMissingKeysUsing)', function (): void {
    beforeEach(function (): void {
        config()->set('transl.reporting.silently_discard_exceptions', true);

        Configuration::refreshInstance(config('transl'));
    });

    it('is able to NOT throw back thrown exceptions', function (): void {
        app()->singleton('translator', static function (): TranslatorContract {
            return new class (app('translation.loader'), 'jp') extends Translator {
                public function handleMissingKeysUsing(?callable $callback): void
                {
                    throw new Exception('Oops');
                }
            };
        });

        expect(__('nope'))->toEqual('nope');

        expect(empty(app(MissingTranslationKeys::class)->queued()))->toEqual(true);
    });
});
