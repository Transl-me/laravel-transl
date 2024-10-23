<?php

declare(strict_types=1);

use Transl\Actions\Commands\AbstractCommandAction;

class Action extends AbstractCommandAction
{
    //
}

it('can be specified a set of locales to accept', function (): void {
    $locales = (new Action())
        ->acceptsLocales(['en', 'fr'])
        ->acceptedLocales();

    expect($locales)->toEqual(['en', 'fr']);
});

it('can be specified a set of groups to accept', function (): void {
    $groups = (new Action())
        ->acceptsGroups(['auth', 'pages/home/nav'])
        ->acceptedGroups();

    expect($groups)->toEqual(['auth', 'pages/home/nav']);
});

it('can be specified a set of namespaces to accept', function (): void {
    $locales = (new Action())
        ->acceptsNamespaces(['package1', 'package2'])
        ->acceptedNamespaces();

    expect($locales)->toEqual(['package1', 'package2']);
});

it('can be specified a set of locales to reject', function (): void {
    $locales = (new Action())
        ->rejectsLocales(['en', 'fr'])
        ->rejectedLocales();

    expect($locales)->toEqual(['en', 'fr']);
});

it('can be specified a set of groups to reject', function (): void {
    $groups = (new Action())
        ->rejectsGroups(['auth', 'pages/home/nav'])
        ->rejectedGroups();

    expect($groups)->toEqual(['auth', 'pages/home/nav']);
});

it('can be specified a set of namespaces to reject', function (): void {
    $locales = (new Action())
        ->rejectsNamespaces(['package1', 'package2'])
        ->rejectedNamespaces();

    expect($locales)->toEqual(['package1', 'package2']);
});
