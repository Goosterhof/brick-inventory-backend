<?php

declare(strict_types=1);

describe('Application', function (): void {
    it('should return a successful response', function (): void {
        $response = $this->get('/api');

        $response->assertStatus(200);
    });
});
