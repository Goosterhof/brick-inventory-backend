<?php

declare(strict_types=1);

use App\Http\Resources\ResourceData;

/*
|--------------------------------------------------------------------------
| ResourceData Architecture
|--------------------------------------------------------------------------
|
| ResourceData classes are DTO-style API response objects that:
| - End with "ResourceData" suffix
| - Are readonly (immutable)
| - Are final (concrete classes) or abstract (base class only)
| - Extend the ResourceData base class
|
*/

arch('resource data classes should end with ResourceData')
    ->expect('App\Http\Resources')
    ->toHaveSuffix('ResourceData');

arch('resource data classes should be readonly')
    ->expect('App\Http\Resources')
    ->toBeReadonly();

it('should have ResourceData as abstract readonly base class', function (): void {
    $reflection = new ReflectionClass(ResourceData::class);

    expect($reflection->isAbstract())->toBeTrue('ResourceData base class should be abstract')
        ->and($reflection->isReadOnly())->toBeTrue('ResourceData base class should be readonly');
});

it('should have all concrete resource data classes as final', function (): void {
    foreach (getClassesInDirectory(dirname(__DIR__, 2) . '/app/Http/Resources', 'App\\Http\\Resources\\') as $className) {
        $reflection = new ReflectionClass($className);

        // Skip abstract classes (like ResourceData base class)
        if ($reflection->isAbstract()) {
            continue;
        }

        expect($reflection->isFinal())->toBeTrue(
            sprintf('Resource class %s should be final', $className),
        );
    }
});

it('should have from method in concrete resource data classes', function (): void {
    foreach (getClassesInDirectory(dirname(__DIR__, 2) . '/app/Http/Resources', 'App\\Http\\Resources\\') as $className) {
        $reflection = new ReflectionClass($className);

        // Skip abstract classes (like ResourceData base class)
        if ($reflection->isAbstract()) {
            continue;
        }

        expect($reflection->hasMethod('from'))->toBeTrue(
            sprintf('ResourceData class %s should have a from() method', $className),
        );
    }
});
