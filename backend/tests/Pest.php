<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may want to write some testing code specific to your
| project that you don't want to repeat in every test file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createTestUser(\App\Enums\Role $role, string $deptCode = 'FINANCE_ICT'): \App\Models\User
{
    $dept = \App\Models\Department::where('code', $deptCode)->first();

    return \App\Models\User::create([
        'name' => 'User',
        'email' => $role->value . '@' . \Illuminate\Support\Str::random(5) . '.com',
        'username' => $role->value . '_' . \Illuminate\Support\Str::random(5),
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
        'role' => $role->value,
        'department_id' => $dept->id,
        'is_active' => true,
    ]);
}

function something()
{
    // ..
}
