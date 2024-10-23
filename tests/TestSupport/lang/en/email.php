<?php

declare(strict_types=1);

use Illuminate\Support\Stringable;

$emails = [
    'contact@example.com',
    'help@example.com',
    'sales@example.com',
];

/**
 * This file exist to test out translation lines
 * created programmatically and lines with strinyfiable values.
 */
return array_reduce($emails, static function (array $acc, string $email): array {
    $email = str($email);
    $label = $email->before('@')->title()->value();

    $acc[$label] = $email;

    return $acc;
}, []);
