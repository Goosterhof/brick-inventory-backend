<?php

declare(strict_types=1);

use App\Http\Resources\ResourceData;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Architecture Test Helpers
|--------------------------------------------------------------------------
|
| These helper functions provide utilities for reflection-based architecture
| tests. We use custom tests when Pest's arch() expectations don't support
| the required logic.
|
| Limitations of Pest arch() to be aware of:
| - ignoring() doesn't work as a filter after expect()
| - toExtend() is a requirement, not a filter (all classes must extend)
| - or() doesn't combine conditions as expected (e.g., "final OR abstract")
|
| For complex conditions, use custom reflection-based tests instead.
|
*/

/**
 * Get all class names in a directory matching a namespace.
 *
 * @return list<class-string>
 */
function getClassesInDirectory(string $directory, string $namespace): array
{
    if (!is_dir($directory)) {
        return [];
    }

    $classes = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $relativePath = str_replace([$directory . '/', '.php'], ['', ''], $file->getPathname());
            $classes[] = $namespace . str_replace('/', '\\', $relativePath);
        }
    }

    return $classes;
}

/**
 * Get all test files in the Feature and Unit directories.
 *
 * @return list<string>
 */
function getTestFiles(): array
{
    $testsDir = dirname(__DIR__);
    $testFiles = [];

    foreach (['Feature', 'Unit'] as $dir) {
        $path = $testsDir . '/' . $dir;
        if (!is_dir($path)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $testFiles[] = $file->getPathname();
            }
        }
    }

    return $testFiles;
}

/*
|--------------------------------------------------------------------------
| Controller Architecture
|--------------------------------------------------------------------------
*/

arch('controllers should end with Controller')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('controllers should not use DB facade')
    ->expect('App\Http\Controllers')
    ->not->toUse(DB::class);

arch('controllers should not use Eloquent Builder directly')
    ->expect('App\Http\Controllers')
    ->not->toUse(Builder::class);

/*
|--------------------------------------------------------------------------
| Model Architecture
|--------------------------------------------------------------------------
*/

arch('models should extend Illuminate\Database\Eloquent\Model')
    ->expect('App\Models')
    ->toExtend(Model::class);

it('should have @property annotations in models', function (): void {
    foreach (getClassesInDirectory(dirname(__DIR__, 2) . '/app/Models', 'App\\Models\\') as $className) {
        // Skip User model as it may have special Laravel requirements
        if ($className === User::class) {
            continue;
        }

        $reflection = new ReflectionClass($className);
        $docComment = $reflection->getDocComment();

        expect($docComment)->not->toBeFalse(
            sprintf('Model %s should have a docblock with @property annotations', $className),
        );

        expect(str_contains($docComment, '@property'))->toBeTrue(
            sprintf('Model %s should have @property annotations in its docblock', $className),
        );
    }
});

it('should not have fillable property in models', function (): void {
    foreach (getClassesInDirectory(dirname(__DIR__, 2) . '/app/Models', 'App\\Models\\') as $className) {
        // Skip User model as it may have special Laravel requirements
        if ($className === User::class) {
            continue;
        }

        $reflection = new ReflectionClass($className);
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
    foreach (getClassesInDirectory(dirname(__DIR__, 2) . '/app/Models', 'App\\Models\\') as $className) {
        // Skip User model as it may have special Laravel requirements
        if ($className === User::class) {
            continue;
        }

        $reflection = new ReflectionClass($className);
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

it('should have family relationship in models with family_id', function (): void {
    foreach (getClassesInDirectory(dirname(__DIR__, 2) . '/app/Models', 'App\\Models\\') as $className) {
        $reflection = new ReflectionClass($className);
        $docComment = $reflection->getDocComment();

        if ($docComment === false) {
            continue;
        }

        // Check if model has family_id property annotation
        if (!preg_match('/@property.*\$family_id/', $docComment)) {
            continue;
        }

        // Model has family_id, so it should have a family() method
        expect($reflection->hasMethod('family'))->toBeTrue(
            sprintf('Model %s has family_id but is missing family() relationship method', $className),
        );
    }
});

/*
|--------------------------------------------------------------------------
| Enum Architecture
|--------------------------------------------------------------------------
*/

arch('enums should be backed enums')
    ->expect('App\Enums')
    ->toBeEnums();

it('should have string or int backing type in enums', function (): void {
    foreach (getClassesInDirectory(dirname(__DIR__, 2) . '/app/Enums', 'App\\Enums\\') as $className) {
        $reflection = new ReflectionEnum($className);

        expect($reflection->isBacked())->toBeTrue(
            sprintf('Enum %s should be a backed enum (string or int)', $className),
        );
    }
});

/*
|--------------------------------------------------------------------------
| Contract Architecture
|--------------------------------------------------------------------------
*/

arch('contracts should be interfaces')
    ->expect('App\Contracts')
    ->toBeInterfaces();

arch('contracts should end with Interface')
    ->expect('App\Contracts')
    ->toHaveSuffix('Interface');

/*
|--------------------------------------------------------------------------
| Data Transfer Object Architecture
|--------------------------------------------------------------------------
*/

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
            fn (ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === $className,
        );

        $methodNames = array_map(fn (ReflectionMethod $method): string => $method->getName(), $methods);
        $nonConstructorMethods = array_diff($methodNames, ['__construct']);

        expect($nonConstructorMethods)->toBeEmpty(
            sprintf('DTO %s should only have __construct, found: %s', $className, implode(', ', $methodNames)),
        );
    }
});

/*
|--------------------------------------------------------------------------
| Request Architecture
|--------------------------------------------------------------------------
*/

arch('requests should end with Request')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request');

/*
|--------------------------------------------------------------------------
| Service Architecture
|--------------------------------------------------------------------------
*/

arch('services should end with Service')
    ->expect('App\Services')
    ->toHaveSuffix('Service');

arch('services should be final')
    ->expect('App\Services')
    ->toBeFinal();

arch('services should not extend anything')
    ->expect('App\Services')
    ->toExtendNothing();

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

/*
|--------------------------------------------------------------------------
| Action Architecture
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| General Code Quality
|--------------------------------------------------------------------------
*/

arch('no debugging statements')
    ->expect('App')
    ->not->toUse(['dd', 'dump', 'var_dump', 'ray']);

arch('all files should use strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('no env calls outside config')
    ->expect('App')
    ->not->toUse('env');

arch('no sleep calls in application code')
    ->expect('App')
    ->not->toUse('sleep');

/*
|--------------------------------------------------------------------------
| Test File Conventions
|--------------------------------------------------------------------------
*/

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

it('should use RefreshDatabase in feature tests', function (): void {
    $featureDir = dirname(__DIR__) . '/Feature';
    if (!is_dir($featureDir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($featureDir, RecursiveDirectoryIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        if ($file->getExtension() !== 'php') {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        $relativePath = str_replace(dirname(__DIR__) . '/', '', $file->getPathname());

        expect(str_contains($content, 'RefreshDatabase'))
            ->toBeTrue(sprintf('Feature test %s should use RefreshDatabase trait', $relativePath));
    }
});

it('should not use RefreshDatabase in unit tests', function (): void {
    $unitDir = dirname(__DIR__) . '/Unit';
    if (!is_dir($unitDir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($unitDir, RecursiveDirectoryIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        if ($file->getExtension() !== 'php') {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        $relativePath = str_replace(dirname(__DIR__) . '/', '', $file->getPathname());

        expect(str_contains($content, 'RefreshDatabase'))
            ->toBeFalse(sprintf('Unit test %s should NOT use RefreshDatabase - use mocks instead', $relativePath));
    }
});

/*
|--------------------------------------------------------------------------
| Migration Architecture
|--------------------------------------------------------------------------
*/

function getMigrationFiles(): array
{
    $migrationsDir = dirname(__DIR__, 2) . '/database/migrations';

    return glob($migrationsDir . '/*.php') ?: [];
}

it('should not have cascade deletes in migrations', function (): void {
    foreach (getMigrationFiles() as $file) {
        $content = file_get_contents($file);
        $filename = basename((string) $file);

        // Strip comments to avoid false positives
        $contentWithoutComments = preg_replace('/\/\*.*?\*\/|\/\/.*$/ms', '', $content);

        // Check for onDelete('cascade') or onDelete("cascade") with flexible whitespace
        expect(preg_match('/->onDelete\s*\(\s*[\'"]cascade[\'"]\s*\)/i', (string) $contentWithoutComments))
            ->toBe(0, sprintf('Migration %s should not use onDelete(cascade) - handle in Action classes', $filename));

        // Check for cascadeOnDelete() with flexible whitespace
        expect(preg_match('/->cascadeOnDelete\s*\(\s*\)/i', (string) $contentWithoutComments))
            ->toBe(0, sprintf('Migration %s should not use cascadeOnDelete() - handle in Action classes', $filename));
    }
});

it('should use anonymous classes in migrations', function (): void {
    foreach (getMigrationFiles() as $file) {
        $content = file_get_contents($file);
        $filename = basename((string) $file);

        expect(str_contains($content, 'return new class extends Migration'))
            ->toBeTrue(sprintf('Migration %s should use anonymous class syntax', $filename));
    }
});

it('should have void return types in migration methods', function (): void {
    foreach (getMigrationFiles() as $file) {
        $content = file_get_contents($file);
        $filename = basename((string) $file);

        expect(preg_match('/public function up\(\)\s*:\s*void/', $content))
            ->toBe(1, sprintf('Migration %s up() method should have void return type', $filename));

        expect(preg_match('/public function down\(\)\s*:\s*void/', $content))
            ->toBe(1, sprintf('Migration %s down() method should have void return type', $filename));
    }
});

it('should use strict types in migrations', function (): void {
    foreach (getMigrationFiles() as $file) {
        $content = file_get_contents($file);
        $filename = basename((string) $file);

        expect(str_contains($content, 'declare(strict_types=1)'))
            ->toBeTrue(sprintf('Migration %s should declare strict types', $filename));
    }
});

/*
|--------------------------------------------------------------------------
| Factory Architecture
|--------------------------------------------------------------------------
*/

arch('factories should end with Factory')
    ->expect('Database\Factories')
    ->toHaveSuffix('Factory');

arch('factories should extend Eloquent Factory')
    ->expect('Database\Factories')
    ->toExtend(Factory::class);
