<?php

declare(strict_types=1);

use App\Models\StorageOptionPart;
use App\Models\User;
use App\Policies\StorageOptionPartPolicy;

describe('StorageOptionPartPolicy', function (): void {
    it('should allow delete', function (): void {
        $user = Mockery::mock(User::class);
        $storageOptionPart = Mockery::mock(StorageOptionPart::class);

        expect(new StorageOptionPartPolicy()->delete($user, $storageOptionPart))->toBeTrue();
    });
});
