<?php

declare(strict_types=1);

use App\Models\StorageOption;
use App\Models\User;
use App\Policies\StorageOptionPolicy;

describe('StorageOptionPolicy', function (): void {
    beforeEach(function (): void {
        $this->policy = new StorageOptionPolicy;
        $this->user = Mockery::mock(User::class);
        $this->user->shouldReceive('getAttribute')->with('family_id')->andReturn(1);
        $this->storageOption = Mockery::mock(StorageOption::class);
        $this->storageOption->shouldReceive('getAttribute')->with('family_id')->andReturn(1);
    });

    it('should allow viewAny', function (): void {
        expect($this->policy->viewAny($this->user))->toBeTrue();
    });

    it('should allow view', function (): void {
        expect($this->policy->view($this->user, $this->storageOption))->toBeTrue();
    });

    it('should allow create', function (): void {
        expect($this->policy->create($this->user))->toBeTrue();
    });

    it('should allow update', function (): void {
        expect($this->policy->update($this->user, $this->storageOption))->toBeTrue();
    });

    it('should allow delete', function (): void {
        expect($this->policy->delete($this->user, $this->storageOption))->toBeTrue();
    });

    it('should allow assignPart', function (): void {
        expect($this->policy->assignPart($this->user, $this->storageOption))->toBeTrue();
    });

    it('should allow viewParts', function (): void {
        expect($this->policy->viewParts($this->user, $this->storageOption))->toBeTrue();
    });

    it('should deny view for user from different family', function (): void {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('getAttribute')->with('family_id')->andReturn(2);

        expect($this->policy->view($user, $storageOption))->toBeFalse();
    });

    it('should deny update for user from different family', function (): void {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('getAttribute')->with('family_id')->andReturn(2);

        expect($this->policy->update($user, $storageOption))->toBeFalse();
    });

    it('should deny delete for user from different family', function (): void {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('getAttribute')->with('family_id')->andReturn(2);

        expect($this->policy->delete($user, $storageOption))->toBeFalse();
    });

    it('should deny assignPart for user from different family', function (): void {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('getAttribute')->with('family_id')->andReturn(2);

        expect($this->policy->assignPart($user, $storageOption))->toBeFalse();
    });

    it('should deny viewParts for user from different family', function (): void {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('getAttribute')->with('family_id')->andReturn(2);

        expect($this->policy->viewParts($user, $storageOption))->toBeFalse();
    });
});
