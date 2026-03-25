<?php

declare(strict_types=1);

use App\Models\User;
use App\Policies\FamilyPolicy;

describe('FamilyPolicy', function (): void {
    beforeEach(function (): void {
        $this->policy = new FamilyPolicy;
    });

    describe('viewMembers', function (): void {
        it('should allow any authenticated user to view family members', function (): void {
            $user = Mockery::mock(User::class);

            expect($this->policy->viewMembers($user))->toBeTrue();
        });
    });

    describe('viewParts', function (): void {
        it('should allow any authenticated user to view family parts', function (): void {
            $user = Mockery::mock(User::class);

            expect($this->policy->viewParts($user))->toBeTrue();
        });
    });

    describe('viewStats', function (): void {
        it('should allow any authenticated user to view family stats', function (): void {
            $user = Mockery::mock(User::class);

            expect($this->policy->viewStats($user))->toBeTrue();
        });
    });

    describe('setRebrickableToken', function (): void {
        it('should allow family head to set rebrickable token', function (): void {
            $user = Mockery::mock(User::class);
            $user->shouldReceive('getAttribute')->with('family')->andReturn((object) ['head_id' => 42]);
            $user->shouldReceive('getAttribute')->with('id')->andReturn(42);

            expect($this->policy->setRebrickableToken($user))->toBeTrue();
        });

        it('should deny non-head member from setting rebrickable token', function (): void {
            $user = Mockery::mock(User::class);
            $user->shouldReceive('getAttribute')->with('family')->andReturn((object) ['head_id' => 42]);
            $user->shouldReceive('getAttribute')->with('id')->andReturn(99);

            expect($this->policy->setRebrickableToken($user))->toBeFalse();
        });
    });
});
