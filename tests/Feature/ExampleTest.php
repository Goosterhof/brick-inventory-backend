<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Application', function (): void {
    it('should return a successful response', function (): void {
        $response = $this->get('/api');

        $response->assertStatus(200);
    });
});
