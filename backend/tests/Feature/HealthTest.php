<?php

test('health endpoint returns ok status and app info', function () {
    $response = $this->get('/api/health');

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'timestamp',
            'database',
            'app',
            'version',
        ]);

    expect($response->json('status'))->toBe('ok')
        ->and($response->json('app'))->toBe('SistemTLHP')
        ->and($response->json('version'))->toBe('1.0.0');
});
