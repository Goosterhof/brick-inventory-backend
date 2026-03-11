<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Exception Architecture
|--------------------------------------------------------------------------
|
| Exceptions follow a structured hierarchy:
| - Abstract base classes (ExternalApiException) define shared structure
| - Concrete leaf classes (no subclasses) must be final
| - Named static constructors preferred over raw `new`
|
*/

arch('exceptions should live in the Exceptions namespace')
    ->expect('App\Exceptions')
    ->toBeClasses();

it('should have all leaf exception classes as final', function (): void {
    $exceptionsDir = dirname(__DIR__, 2) . '/app/Exceptions';
    $classes = getClassesInDirectory($exceptionsDir, 'App\\Exceptions\\');

    // Build parent-child map to identify which classes have subclasses
    $hasSubclass = [];
    foreach ($classes as $className) {
        $reflection = new ReflectionClass($className);
        $parent = $reflection->getParentClass();

        if ($parent !== false && str_starts_with($parent->getName(), 'App\\Exceptions\\')) {
            $hasSubclass[$parent->getName()] = true;
        }
    }

    $nonFinalLeaves = [];
    foreach ($classes as $class) {
        $reflection = new ReflectionClass($class);

        // Skip abstract classes — they cannot be final
        if ($reflection->isAbstract()) {
            continue;
        }

        // Skip classes that have subclasses — they cannot be final
        if (isset($hasSubclass[$class])) {
            continue;
        }

        // Leaf concrete class — must be final
        $file = $reflection->getFileName();
        $content = (string) shell_exec('cat ' . escapeshellarg($file));

        if (!str_contains($content, 'final class')) {
            $nonFinalLeaves[] = $class;
        }
    }

    expect($nonFinalLeaves)->toBeEmpty(
        'Leaf exception classes (no subclasses) must be final: ' . implode(', ', $nonFinalLeaves),
    );
});
