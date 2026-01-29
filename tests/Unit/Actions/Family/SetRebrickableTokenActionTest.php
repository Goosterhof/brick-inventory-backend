<?php

declare(strict_types=1);

use App\Actions\Family\SetRebrickableTokenAction;
use App\Contracts\Family\SetRebrickableTokenInterface;
use App\Models\Family;

describe('SetRebrickableTokenAction', function (): void {
    it('should set the rebrickable user token on the family', function (): void {
        // arrange
        $family = Mockery::mock(Family::class)->makePartial();
        $family->shouldReceive('save')->once();

        $data = new class implements SetRebrickableTokenInterface
        {
            public string $rebrickableUserToken = 'my-secret-token';
        };

        $action = new SetRebrickableTokenAction;

        // act
        $action->execute($family, $data);

        // assert
        expect($family->rebrickable_user_token)->toBe('my-secret-token');
    });

    it('should return the updated family', function (): void {
        // arrange
        $family = Mockery::mock(Family::class)->makePartial();
        $family->shouldReceive('save');

        $data = new class implements SetRebrickableTokenInterface
        {
            public string $rebrickableUserToken = 'another-token';
        };

        $action = new SetRebrickableTokenAction;

        // act
        $result = $action->execute($family, $data);

        // assert
        expect($result)->toBe($family);
    });

    it('should overwrite existing token', function (): void {
        // arrange
        $family = Mockery::mock(Family::class)->makePartial();
        $family->rebrickable_user_token = 'old-token';
        $family->shouldReceive('save')->once();

        $data = new class implements SetRebrickableTokenInterface
        {
            public string $rebrickableUserToken = 'new-token';
        };

        $action = new SetRebrickableTokenAction;

        // act
        $action->execute($family, $data);

        // assert
        expect($family->rebrickable_user_token)->toBe('new-token');
    });
});
