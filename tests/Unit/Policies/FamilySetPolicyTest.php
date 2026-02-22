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

    it('should allow view for user from same family', function (): void {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        expect($this->policy->view($user, $familySet))->toBeTrue();
    });

    it('should allow create', function (): void {
        $user = Mockery::mock(User::class);

        expect($this->policy->create($user))->toBeTrue();
    });

    it('should allow update for user from same family', function (): void {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        expect($this->policy->update($user, $familySet))->toBeTrue();
    });

    it('should allow delete for user from same family', function (): void {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        expect($this->policy->delete($user, $familySet))->toBeTrue();
    });

    it('should deny view for user from different family', function (): void {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('getAttribute')->with('family_id')->andReturn(2);

        expect($this->policy->view($user, $familySet))->toBeFalse();
    });

    it('should deny update for user from different family', function (): void {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('getAttribute')->with('family_id')->andReturn(2);

        expect($this->policy->update($user, $familySet))->toBeFalse();
    });

    it('should deny delete for user from different family', function (): void {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        $familySet = Mockery::mock(FamilySet::class);
        $familySet->shouldReceive('getAttribute')->with('family_id')->andReturn(2);

        expect($this->policy->delete($user, $familySet))->toBeFalse();
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
