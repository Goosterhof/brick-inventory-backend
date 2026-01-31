<?php

declare(strict_types=1);

use App\Http\Resources\ResourceData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Controller Architecture
|--------------------------------------------------------------------------
|
| Controllers are thin HTTP handlers that:
| - End with "Controller" suffix
| - Delegate business logic to Action classes
| - Return JsonResponse or array (for collections)
| - Do NOT use try-catch blocks (exception handling is global)
| - Do NOT return ResourceData directly (use ->toResponse() instead)
|
*/

/**
 * Extract all type names from a reflection type (handles named, union, and intersection types).
 *
 * @return list<string>
 */
function getTypeNames(ReflectionType $reflectionType): array
{
    if ($reflectionType instanceof ReflectionNamedType) {
        return [$reflectionType->getName()];
    }

    if ($reflectionType instanceof ReflectionUnionType || $reflectionType instanceof ReflectionIntersectionType) {
        $names = [];
        foreach ($reflectionType->getTypes() as $subType) {
            $names = array_merge($names, getTypeNames($subType));
        }

        return $names;
    }

    return [];
}

arch('controllers should end with Controller')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('controllers should not use DB facade')
    ->expect('App\Http\Controllers')
    ->not->toUse(DB::class);

arch('controllers should not use Eloquent Builder directly')
    ->expect('App\Http\Controllers')
    ->not->toUse(Builder::class);

it('should have controller methods return JsonResponse or array', function (): void {
    $allowedReturnTypes = [JsonResponse::class, 'array'];

    foreach (getClassesInDirectory(dirname(__DIR__, 2) . '/app/Http/Controllers', 'App\\Http\\Controllers\\') as $className) {
        $reflection = new ReflectionClass($className);

        // Skip abstract base Controller class
        if ($reflection->isAbstract()) {
            continue;
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip inherited methods and constructor
            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            if ($method->getName() === '__construct') {
                continue;
            }

            $returnType = $method->getReturnType();

            expect($returnType)->not->toBeNull(
                sprintf('Controller method %s::%s() should have a return type', $className, $method->getName()),
            );

            $typeNames = getTypeNames($returnType);
            foreach ($typeNames as $typeName) {
                expect(in_array($typeName, $allowedReturnTypes, true))->toBeTrue(
                    sprintf(
                        'Controller method %s::%s() should return JsonResponse or array, got %s',
                        $className,
                        $method->getName(),
                        $typeName,
                    ),
                );
            }
        }
    }
});

it('should not return ResourceData directly from controller methods', function (): void {
    foreach (getClassesInDirectory(dirname(__DIR__, 2) . '/app/Http/Controllers', 'App\\Http\\Controllers\\') as $className) {
        $reflection = new ReflectionClass($className);

        // Skip abstract base Controller class
        if ($reflection->isAbstract()) {
            continue;
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip inherited methods and constructor
            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            if ($method->getName() === '__construct') {
                continue;
            }

            $returnType = $method->getReturnType();
            if ($returnType === null) {
                continue;
            }

            $typeNames = getTypeNames($returnType);
            foreach ($typeNames as $typeName) {
                // Check if return type is a ResourceData subclass
                if (class_exists($typeName) && is_subclass_of($typeName, ResourceData::class)) {
                    expect(false)->toBeTrue(
                        sprintf(
                            'Controller method %s::%s() should not return ResourceData directly. Use ->toResponse() instead.',
                            $className,
                            $method->getName(),
                        ),
                    );
                }
            }
        }
    }
});

it('should not use try-catch blocks in controllers', function (): void {
    $controllersDir = dirname(__DIR__, 2) . '/app/Http/Controllers';

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($controllersDir, RecursiveDirectoryIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        if ($file->getExtension() !== 'php') {
            continue;
        }

        $filename = $file->getFilename();

        // Skip base Controller class
        if ($filename === 'Controller.php') {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        $tokens = token_get_all($content);
        $relativePath = str_replace($controllersDir . '/', '', $file->getPathname());

        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_TRY) {
                expect(false)->toBeTrue(
                    sprintf(
                        'Controller %s should not use try-catch blocks. Exception handling is done globally in bootstrap/app.php.',
                        $relativePath,
                    ),
                );
            }
        }
    }
});
