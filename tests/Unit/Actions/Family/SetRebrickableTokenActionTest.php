<?php

declare(strict_types=1);

use App\Actions\Family\SetRebrickableTokenAction;
use App\Contracts\Family\SetRebrickableTokenInterface;
use App\Exceptions\NotFamilyHeadException;
use App\Models\Family;
use App\Models\User;

describe('SetRebrickableTokenAction', function (): void {
    it('should set the rebrickable user token on the family', function (): void {
        // arrange
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $family = Mockery::mock(Family::class)->makePartial();
        $family->head_id = 1;
        $family->shouldReceive('save')->once();

        $data = new class implements SetRebrickableTokenInterface
        {
            public string $rebrickableUserToken = 'my-secret-token';
        };

        $action = new SetRebrickableTokenAction;

        // act
        $action->execute($family, $data, $user);

        // assert
        expect($family->rebrickable_user_token)->toBe('my-secret-token');
    });

    it('should return the updated family', function (): void {
        // arrange
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $family = Mockery::mock(Family::class)->makePartial();
        $family->head_id = 1;
        $family->shouldReceive('save');

        $data = new class implements SetRebrickableTokenInterface
        {
            public string $rebrickableUserToken = 'another-token';
        };

        $action = new SetRebrickableTokenAction;

        // act
        $result = $action->execute($family, $data, $user);

        // assert
        expect($result)->toBe($family);
    });

    it('should overwrite existing token', function (): void {
        // arrange
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;

        $family = Mockery::mock(Family::class)->makePartial();
        $family->head_id = 1;
        $family->rebrickable_user_token = 'old-token';
        $family->shouldReceive('save')->once();

        $data = new class implements SetRebrickableTokenInterface
        {
            public string $rebrickableUserToken = 'new-token';
        };

        $action = new SetRebrickableTokenAction;

        // act
        $action->execute($family, $data, $user);

        // assert
        expect($family->rebrickable_user_token)->toBe('new-token');
    });

    it('should throw NotFamilyHeadException when user is not the family head', function (): void {
        // arrange
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 2;

        $family = Mockery::mock(Family::class)->makePartial();
        $family->head_id = 1;
        $family->shouldReceive('save')->never();

        $data = new class implements SetRebrickableTokenInterface
        {
            public string $rebrickableUserToken = 'my-token';
        };

        $action = new SetRebrickableTokenAction;

        // act & assert
        expect(fn (): Family => $action->execute($family, $data, $user))
            ->toThrow(NotFamilyHeadException::class);
    });

    it('should allow action when user is the family head', function (): void {
        // arrange
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 5;

        $family = Mockery::mock(Family::class)->makePartial();
        $family->head_id = 5;
        $family->shouldReceive('save')->once();

        $data = new class implements SetRebrickableTokenInterface
        {
            public string $rebrickableUserToken = 'valid-token';
        };

        $action = new SetRebrickableTokenAction;

        // act
        $result = $action->execute($family, $data, $user);

        // assert
        expect($result)->toBe($family)
            ->and($family->rebrickable_user_token)->toBe('valid-token');
    });
});
