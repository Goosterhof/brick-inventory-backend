<?php

declare(strict_types=1);

use App\Http\Controllers\FamilySetController;
use App\Http\Controllers\StorageOptionController;
use Illuminate\Contracts\Auth\Access\Gate;

/*
|--------------------------------------------------------------------------
| Policy Architecture
|--------------------------------------------------------------------------
|
| Policies are authorization gates that:
| - End with "Policy" suffix
| - Are final readonly classes
| - Have methods that return bool
| - Use single-tier model (no interaction tier — unlike issue-tracker)
|
*/

arch('policies should end with Policy')
    ->expect('App\Policies')
    ->toHaveSuffix('Policy');

it('should have all policy classes as final readonly', function (): void {
    $nonFinalReadonly = [];

    foreach (getClassesInDirectory(dirname(__DIR__, 2) . '/app/Policies', 'App\\Policies\\') as $className) {
        $file = new ReflectionClass($className)->getFileName();
        $content = (string) shell_exec('cat ' . escapeshellarg($file));

        if (!str_contains($content, 'final readonly class')) {
            $nonFinalReadonly[] = $className;
        }
    }

    expect($nonFinalReadonly)->toBeEmpty(
        'These policies are not final readonly: ' . implode(', ', $nonFinalReadonly),
    );
});

it('should have all policy methods return bool', function (): void {
    foreach (getClassesInDirectory(dirname(__DIR__, 2) . '/app/Policies', 'App\\Policies\\') as $className) {
        $reflection = new ReflectionClass($className);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            if ($method->getName() === '__construct') {
                continue;
            }

            $returnType = $method->getReturnType();

            expect($returnType)->not->toBeNull(
                sprintf('Policy method %s::%s() should have a return type', $className, $method->getName()),
            );

            expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
            expect($returnType->getName())->toBe('bool',
                sprintf('Policy method %s::%s() should return bool, got %s', $className, $method->getName(), $returnType->getName()),
            );
        }
    }
});

it('should have gate authorization in controllers that have policies', function (): void {
    $controllersWithPolicies = [
        FamilySetController::class,
        StorageOptionController::class,
    ];

    foreach ($controllersWithPolicies as $controllerWithPolicy) {
        $reflection = new ReflectionClass($controllerWithPolicy);
        $constructor = $reflection->getConstructor();

        expect($constructor)->not->toBeNull(
            sprintf('Controller %s should have a constructor', $controllerWithPolicy),
        );

        $hasGate = false;
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && $type->getName() === Gate::class) {
                $hasGate = true;
                break;
            }
        }

        expect($hasGate)->toBeTrue(
            sprintf('Controller %s should inject Illuminate\Contracts\Auth\Access\Gate', $controllerWithPolicy),
        );

        $file = $reflection->getFileName();
        $content = (string) shell_exec('cat ' . escapeshellarg($file));

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $controllerWithPolicy) {
                continue;
            }

            if ($method->getName() === '__construct') {
                continue;
            }

            expect(str_contains($content, '$this->gate->authorize('))->toBeTrue(
                sprintf('Controller %s should use $this->gate->authorize() calls', $controllerWithPolicy),
            );
        }
    }
});
