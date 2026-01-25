<?php

declare(strict_types=1);

use Illuminate\Http\Request;

arch('actions should end with Action')
    ->expect('App\Actions')
    ->toHaveSuffix('Action');

arch('actions should have execute method')
    ->expect('App\Actions')
    ->toHaveMethod('execute');

it('should only have execute as public method in actions', function (): void {
    foreach (getClassesInDirectory(dirname(__DIR__, 2) . '/app/Actions', 'App\\Actions\\') as $className) {
        $reflection = new ReflectionClass($className);
        $publicMethods = array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn (ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === $className,
        );

        $methodNames = array_map(fn (ReflectionMethod $method): string => $method->getName(), $publicMethods);
        $extraMethods = array_diff($methodNames, ['__construct', 'execute']);

        expect($methodNames)->toContain('execute');
        expect($extraMethods)->toBeEmpty(
            sprintf('Action %s should only have __construct and execute as public methods, found: %s', $className, implode(', ', $methodNames)),
        );
    }
});

arch('actions should not depend on request classes directly')
    ->expect('App\Actions')
    ->not->toUse(Request::class);

arch('actions should not depend on controllers')
    ->expect('App\Actions')
    ->not->toUse('App\Http\Controllers');
