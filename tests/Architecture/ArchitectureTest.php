<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

arch('controllers should end with Controller')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('models should extend Illuminate\Database\Eloquent\Model')
    ->expect('App\Models')
    ->toExtend(Model::class);

arch('data transfer objects should end with Data')
    ->expect('App\DataTransferObjects')
    ->toHaveSuffix('Data');

arch('requests should end with Request')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request');

arch('services should end with Service')
    ->expect('App\Services')
    ->toHaveSuffix('Service');

arch('resources should end with Resource')
    ->expect('App\Http\Resources')
    ->toHaveSuffix('Resource');

arch('actions should end with Action')
    ->expect('App\Actions')
    ->toHaveSuffix('Action');

arch('actions should have execute method')
    ->expect('App\Actions')
    ->toHaveMethod('execute');

arch('no debugging statements')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'var_dump', 'ray']);

arch('all files should use strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('data transfer objects should be readonly')
    ->expect('App\DataTransferObjects')
    ->toBeReadonly();

arch('data transfer objects should be final')
    ->expect('App\DataTransferObjects')
    ->toBeFinal();

function getDtoClasses(): array
{
    $dtosDir = dirname(__DIR__, 2) . '/app/DataTransferObjects';
    $classes = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dtosDir, RecursiveDirectoryIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativePath = str_replace([$dtosDir . '/', '.php'], ['', ''], $file->getPathname());
            $className = 'App\\DataTransferObjects\\' . str_replace('/', '\\', $relativePath);
            $classes[] = $className;
        }
    }

    return $classes;
}

it('should not have methods in DTOs', function (): void {
    foreach (getDtoClasses() as $className) {
        $reflection = new ReflectionClass($className);
        $methods = array_filter(
            $reflection->getMethods(),
            fn (ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === $className,
        );

        $methodNames = array_map(fn (ReflectionMethod $method): string => $method->getName(), $methods);
        $nonConstructorMethods = array_diff($methodNames, ['__construct']);

        expect($nonConstructorMethods)->toBeEmpty(
            sprintf('DTO %s should only have __construct, found: %s', $className, implode(', ', $methodNames)),
        );
    }
});

function getActionClasses(): array
{
    $actionsDir = dirname(__DIR__, 2) . '/app/Actions';
    $classes = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($actionsDir, RecursiveDirectoryIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativePath = str_replace([$actionsDir . '/', '.php'], ['', ''], $file->getPathname());
            $className = 'App\\Actions\\' . str_replace('/', '\\', $relativePath);
            $classes[] = $className;
        }
    }

    return $classes;
}

it('should only have execute as public method in actions', function (): void {
    foreach (getActionClasses() as $className) {
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

function getTestFiles(): array
{
    $testsDir = dirname(__DIR__);
    $testFiles = [];

    $directories = ['Feature', 'Unit'];
    foreach ($directories as $dir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($testsDir . '/' . $dir, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $testFiles[] = $file->getPathname();
            }
        }
    }

    return $testFiles;
}

it('should use describe blocks in test files', function (): void {
    foreach (getTestFiles() as $file) {
        $content = file_get_contents($file);
        $relativePath = str_replace(dirname(__DIR__) . '/', '', $file);

        expect(str_contains($content, 'describe('))
            ->toBeTrue(sprintf('Test file %s should use describe() blocks', $relativePath));
    }
});

it('should use it should syntax in test files', function (): void {
    foreach (getTestFiles() as $file) {
        $content = file_get_contents($file);
        $relativePath = str_replace(dirname(__DIR__) . '/', '', $file);

        // Check that test cases use it('should syntax
        if (preg_match_all('/\bit\s*\(\s*[\'"]/', $content)) {
            expect(preg_match('/\bit\s*\(\s*[\'"]should\s/', $content))
                ->toBe(1, sprintf("Test file %s should use it('should ...') syntax", $relativePath));
        }
    }
});

function getModelClasses(): array
{
    $modelsDir = dirname(__DIR__, 2) . '/app/Models';
    $classes = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($modelsDir, RecursiveDirectoryIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativePath = str_replace([$modelsDir . '/', '.php'], ['', ''], $file->getPathname());
            $className = 'App\\Models\\' . str_replace('/', '\\', $relativePath);
            $classes[] = $className;
        }
    }

    return $classes;
}

it('should have @property annotations in models', function (): void {
    foreach (getModelClasses() as $className) {
        $reflection = new ReflectionClass($className);
        $docComment = $reflection->getDocComment();

        // Skip User model as it may have special Laravel requirements
        if ($className === User::class) {
            continue;
        }

        expect($docComment)->not->toBeFalse(
            sprintf('Model %s should have a docblock with @property annotations', $className),
        );

        expect(str_contains($docComment, '@property'))->toBeTrue(
            sprintf('Model %s should have @property annotations in its docblock', $className),
        );
    }
});

it('should not have fillable property in models', function (): void {
    foreach (getModelClasses() as $className) {
        $reflection = new ReflectionClass($className);

        // Skip User model as it may have special Laravel requirements
        if ($className === User::class) {
            continue;
        }

        $hasFillable = false;
        foreach ($reflection->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() === $className && $property->getName() === 'fillable') {
                $hasFillable = true;
                break;
            }
        }

        expect($hasFillable)->toBeFalse(
            sprintf('Model %s should not have $fillable property - use explicit property assignment instead', $className),
        );
    }
});

it('should not have guarded property in models', function (): void {
    foreach (getModelClasses() as $className) {
        $reflection = new ReflectionClass($className);

        // Skip User model as it may have special Laravel requirements
        if ($className === User::class) {
            continue;
        }

        $hasGuarded = false;
        foreach ($reflection->getProperties() as $property) {
            if ($property->getDeclaringClass()->getName() === $className && $property->getName() === 'guarded') {
                $hasGuarded = true;
                break;
            }
        }

        expect($hasGuarded)->toBeFalse(
            sprintf('Model %s should not have $guarded property - use explicit property assignment instead', $className),
        );
    }
});

function getMigrationFiles(): array
{
    $migrationsDir = dirname(__DIR__, 2) . '/database/migrations';

    return glob($migrationsDir . '/*.php') ?: [];
}

it('should not have cascade deletes in migrations', function (): void {
    foreach (getMigrationFiles() as $file) {
        $content = file_get_contents($file);
        $filename = basename($file);

        expect(str_contains($content, "onDelete('cascade')"))
            ->toBeFalse("Migration {$filename} should not use onDelete('cascade') - handle in Action classes");

        expect(str_contains($content, '->cascadeOnDelete()'))
            ->toBeFalse("Migration {$filename} should not use cascadeOnDelete() - handle in Action classes");
    }
});

it('should use anonymous classes in migrations', function (): void {
    foreach (getMigrationFiles() as $file) {
        $content = file_get_contents($file);
        $filename = basename($file);

        expect(str_contains($content, 'return new class extends Migration'))
            ->toBeTrue("Migration {$filename} should use anonymous class syntax");
    }
});

it('should have void return types in migration methods', function (): void {
    foreach (getMigrationFiles() as $file) {
        $content = file_get_contents($file);
        $filename = basename($file);

        expect(preg_match('/public function up\(\):\s*void/', $content))
            ->toBe(1, "Migration {$filename} up() method should have void return type");

        expect(preg_match('/public function down\(\):\s*void/', $content))
            ->toBe(1, "Migration {$filename} down() method should have void return type");
    }
});

it('should use strict types in migrations', function (): void {
    foreach (getMigrationFiles() as $file) {
        $content = file_get_contents($file);
        $filename = basename($file);

        expect(str_contains($content, 'declare(strict_types=1)'))
            ->toBeTrue("Migration {$filename} should declare strict types");
    }
});
