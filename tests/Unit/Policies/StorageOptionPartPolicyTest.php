<?php

declare(strict_types=1);

use App\Models\StorageOption;
use App\Models\StorageOptionPart;
use App\Models\User;
use App\Policies\StorageOptionPartPolicy;

describe('StorageOptionPartPolicy', function (): void {
    it('should allow delete for user from same family', function (): void {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('getAttribute')->with('storageOption')->andReturn($storageOption);

        expect(new StorageOptionPartPolicy()->delete($user, $storageOptionPart))->toBeTrue();
    });

    it('should deny delete for user from different family', function (): void {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('family_id')->andReturn(1);

        $storageOption = Mockery::mock(StorageOption::class);
        $storageOption->shouldReceive('getAttribute')->with('family_id')->andReturn(2);

        $storageOptionPart = Mockery::mock(StorageOptionPart::class);
        $storageOptionPart->shouldReceive('getAttribute')->with('storageOption')->andReturn($storageOption);

        expect(new StorageOptionPartPolicy()->delete($user, $storageOptionPart))->toBeFalse();
    });
});
