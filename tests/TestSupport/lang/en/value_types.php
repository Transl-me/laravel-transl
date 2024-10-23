<?php

declare(strict_types=1);

$stdClass = new stdClass();

$stdClass->null = null;
$stdClass->string = 'hello';
$stdClass->true = true;
$stdClass->false = false;
$stdClass->int = 123;
$stdClass->float = 123.123;
$stdClass->array_empty = [];
$stdClass->array_filled = [
    'attributes' => [
        'address' => [
            'line_1' => 123,
            'line_2' => null,
            'street' => 'abc',
        ],
    ],
];

/**
 * This file exist to test out all kinds of
 * translation line value types.
 */
return [
    'null' => null,
    'string' => 'hello',
    'true' => true,
    'false' => false,
    'int' => 123,
    'float' => 123.123,

    'string_null' => 'null',
    'string_true' => 'true',
    'string_false' => 'false',
    'string_int' => '123',
    'string_float' => '123.123',
    'string_empty' => '',

    'array_filled_null' => [null],
    'array_filled_string' => ['hello'],
    'array_filled_true' => [true],
    'array_filled_false' => [false],
    'array_filled_int' => [123],
    'array_filled_float' => [123.123],
    'array_empty' => [],

    'multi_array_filled_null' => ['hey' => null],
    'multi_array_filled_string' => ['hey' => 'hello'],
    'multi_array_filled_true' => ['hey' => true],
    'multi_array_filled_false' => ['hey' => false],
    'multi_array_filled_int' => ['hey' => 123],
    'multi_array_filled_float' => ['hey' => 123.123],

    'Collection_filled_null' => collect([null]),
    'Collection_filled_string' => collect(['hello']),
    'Collection_filled_true' => collect([true]),
    'Collection_filled_false' => collect([false]),
    'Collection_filled_int' => collect([123]),
    'Collection_filled_float' => collect([123.123]),
    'Collection_empty' => collect([]),

    'multi_Collection_filled_null' => collect(['hey' => null]),
    'multi_Collection_filled_string' => collect(['hey' => 'hello']),
    'multi_Collection_filled_true' => collect(['hey' => true]),
    'multi_Collection_filled_false' => collect(['hey' => false]),
    'multi_Collection_filled_int' => collect(['hey' => 123]),
    'multi_Collection_filled_float' => collect(['hey' => 123.123]),

    'Stringable' => str("i'm_stringable")->replace('_', ' ')->title(),
    'stdClass' => $stdClass,

    'Closure_null' => static fn () => null,
    'Closure_string' => static fn () => 'hello',
    'Closure_true' => static fn () => true,
    'Closure_false' => static fn () => false,
    'Closure_int' => static fn () => 123,
    'Closure_float' => static fn () => 123.123,

    'Closure_string_null' => static fn () => 'null',
    'Closure_string_true' => static fn () => 'true',
    'Closure_string_false' => static fn () => 'false',
    'Closure_string_int' => static fn () => '123',
    'Closure_string_float' => static fn () => '123.123',
    'Closure_string_empty' => static fn () => '',

    'Closure_Collection_filled_null' => static fn () => collect([null]),
    'Closure_Collection_filled_string' => static fn () => collect(['hello']),
    'Closure_Collection_filled_true' => static fn () => collect([true]),
    'Closure_Collection_filled_false' => static fn () => collect([false]),
    'Closure_Collection_filled_int' => static fn () => collect([123]),
    'Closure_Collection_filled_float' => static fn () => collect([123.123]),
    'Closure_Collection_empty' => static fn () => collect([]),

    'Closure_multi_Collection_filled_null' => static fn () => collect(['hey' => null]),
    'Closure_multi_Collection_filled_string' => static fn () => collect(['hey' => 'hello']),
    'Closure_multi_Collection_filled_true' => static fn () => collect(['hey' => true]),
    'Closure_multi_Collection_filled_false' => static fn () => collect(['hey' => false]),
    'Closure_multi_Collection_filled_int' => static fn () => collect(['hey' => 123]),
    'Closure_multi_Collection_filled_float' => static fn () => collect(['hey' => 123.123]),

    'Closure_Stringable' => static fn () => str("i'm_stringable")->replace('_', ' ')->title(),
    'Closure_stdClass' => static fn () => $stdClass,
    'Closure_Closure_stdClass' => static fn () => static fn () => $stdClass,
    'Closure_with_params' => static fn ($value1, $value2) => ['value1' => $value1, 'value2' => $value2],
];
