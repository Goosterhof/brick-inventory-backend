<?php

declare(strict_types=1);

use App\Models\User;
use App\Policies\SetPolicy;

describe('SetPolicy', function (): void {
    beforeEach(function (): void {
        $this->policy = new SetPolicy;
    });

    it('should allow any user to view set parts', function (): void {
        $user = Mockery::mock(User::class);

        expect($this->policy->viewParts($user))->toBeTrue();
    });
});
