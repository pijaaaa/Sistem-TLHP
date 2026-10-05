<?php

beforeEach(function () {
    // Use a registered group: 'departments'
});

test('cache service remembers and retrieves value', function () {
    $value = \App\Support\CacheService::remember('departments', 'foo', fn () => 'bar');

    expect($value)->toBe('bar');
});

test('cache service returns cached value on second call', function () {
    \App\Support\CacheService::remember('departments', 'key', fn () => 'first', 60);

    // Override closure — should return cached value, not re-execute
    $value = \App\Support\CacheService::remember('departments', 'key', fn () => 'second', 60);

    expect($value)->toBe('first');
});

test('cache service flushGroup increments version', function () {
    $before = \App\Support\CacheService::version('departments');

    \App\Support\CacheService::flushGroup('departments');

    $after = \App\Support\CacheService::version('departments');

    expect($after)->toBe($before + 1);
});

test('cache service forget removes value', function () {
    \App\Support\CacheService::remember('departments', 'temp', fn () => 'value', 60);

    \App\Support\CacheService::forget('departments', 'temp');

    // Should re-execute the callback since key was forgotten
    $value = \App\Support\CacheService::remember('departments', 'temp', fn () => 'new-value', 60);

    expect($value)->toBe('new-value');
});

test('cache service throws on unregistered group', function () {
    expect(fn () => \App\Support\CacheService::remember('unregistered', 'key', fn () => 'val'))
        ->toThrow(\InvalidArgumentException::class);
});
