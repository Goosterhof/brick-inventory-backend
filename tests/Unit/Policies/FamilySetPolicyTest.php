<?php

declare(strict_types=1);

use App\Models\FamilySet;
use App\Models\User;
use App\Policies\FamilySetPolicy;

describe('FamilySetPolicy', function (): void {
    beforeEach(function (): void {
        $this->policy = new FamilySetPolicy;
    });

    it('should allow viewAny', function (): void {
        $user = Mockery::mock(User::class);

        expect($this->policy->viewAny($user))->toBeTrue();
    });

    it('should allow view', function (): void {
        $user = Mockery::mock(User::class);
        $familySet = Mockery::mock(FamilySet::class);

        expect($this->policy->view($user, $familySet))->toBeTrue();
    });

    it('should allow create', function (): void {
        $user = Mockery::mock(User::class);

        expect($this->policy->create($user))->toBeTrue();
    });

    it('should allow update', function (): void {
        $user = Mockery::mock(User::class);
        $familySet = Mockery::mock(FamilySet::class);

        expect($this->policy->update($user, $familySet))->toBeTrue();
    });

    it('should allow delete', function (): void {
        $user = Mockery::mock(User::class);
        $familySet = Mockery::mock(FamilySet::class);

        expect($this->policy->delete($user, $familySet))->toBeTrue();
    });

    describe('importFromRebrickable', function (): void {
        it('should allow family head to import', function (): void {
            $user = Mockery::mock(User::class);
            $user->shouldReceive('getAttribute')->with('family')->andReturn((object) ['head_id' => 42]);
            $user->shouldReceive('getAttribute')->with('id')->andReturn(42);

            expect($this->policy->importFromRebrickable($user))->toBeTrue();
        });

        it('should deny non-head family member from importing', function (): void {
            $user = Mockery::mock(User::class);
            $user->shouldReceive('getAttribute')->with('family')->andReturn((object) ['head_id' => 42]);
            $user->shouldReceive('getAttribute')->with('id')->andReturn(99);

            expect($this->policy->importFromRebrickable($user))->toBeFalse();
        });
    });
});
