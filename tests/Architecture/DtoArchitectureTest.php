<?php

declare(strict_types=1);

arch('data transfer objects should end with Data')
    ->expect('App\DataTransferObjects')
    ->toHaveSuffix('Data');

arch('data transfer objects should be readonly')
    ->expect('App\DataTransferObjects')
    ->toBeReadonly();

arch('data transfer objects should be final')
    ->expect('App\DataTransferObjects')
    ->toBeFinal();

it('should not have methods in DTOs', function (): void {
    foreach (getClassesInDirectory(dirname(__DIR__, 2) . '/app/DataTransferObjects', 'App\\DataTransferObjects\\') as $className) {
        $reflection = new ReflectionClass($className);
        $methods = array_filter(
            $reflection->getMethods(),
            fn (ReflectionMethod $reflectionMethod): bool => $reflectionMethod->getDeclaringClass()->getName() === $className,
        );

        $methodNames = array_map(fn (ReflectionMethod $reflectionMethod): string => $reflectionMethod->getName(), $methods);
        $nonConstructorMethods = array_diff($methodNames, ['__construct']);

        expect($nonConstructorMethods)->toBeEmpty(
            sprintf('DTO %s should only have __construct, found: %s', $className, implode(', ', $methodNames)),
        );
    }
});

arch('App\Data DTOs should end with Data')
    ->expect('App\Data')
    ->toHaveSuffix('Data');

arch('App\Data DTOs should be readonly')
    ->expect('App\Data')
    ->toBeReadonly();

arch('App\Data DTOs should be final')
    ->expect('App\Data')
    ->toBeFinal();

it('should not have methods in App\Data DTOs', function (): void {
    $directories = [
        dirname(__DIR__, 2) . '/app/Data' => 'App\\Data\\',
    ];

    foreach ($directories as $directory => $namespace) {
        foreach (getClassesInDirectory($directory, $namespace) as $className) {
            $reflection = new ReflectionClass($className);
            $methods = array_filter(
                $reflection->getMethods(),
                fn (ReflectionMethod $reflectionMethod): bool => $reflectionMethod->getDeclaringClass()->getName() === $className,
            );

            $methodNames = array_map(fn (ReflectionMethod $reflectionMethod): string => $reflectionMethod->getName(), $methods);
            $nonConstructorMethods = array_diff($methodNames, ['__construct']);

            expect($nonConstructorMethods)->toBeEmpty(
                sprintf('DTO %s should only have __construct, found: %s', $className, implode(', ', $methodNames)),
            );
        }
    }
});
