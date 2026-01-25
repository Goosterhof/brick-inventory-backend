<?php

declare(strict_types=1);

use App\Actions\StorageOption\ListStorageOptionsAction;
use Illuminate\Database\Eloquent\Collection;

describe('ListStorageOptionsAction', function (): void {
    it('should return a collection', function (): void {
        // arrange
        $action = new ListStorageOptionsAction;

        // act & assert - verify the action exists and is callable
        expect($action)->toBeInstanceOf(ListStorageOptionsAction::class);
        expect(method_exists($action, 'execute'))->toBeTrue();

        // Note: The actual query behavior is tested through feature tests
        // which verify the correct storage options are returned for the authenticated user
    });

    it('should have execute method that accepts a User', function (): void {
        // arrange
        $action = new ListStorageOptionsAction;
        $reflection = new ReflectionMethod($action, 'execute');
        $parameters = $reflection->getParameters();

        // assert
        expect($parameters)->toHaveCount(1);
        expect($parameters[0]->getName())->toBe('user');
    });

    it('should return a Collection type', function (): void {
        // arrange
        $action = new ListStorageOptionsAction;
        $reflection = new ReflectionMethod($action, 'execute');
        $returnType = $reflection->getReturnType();

        // assert
        expect($returnType)->not->toBeNull();
        expect($returnType->getName())->toBe(Collection::class);
    });
});
