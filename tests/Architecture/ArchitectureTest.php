<?php

declare(strict_types=1);

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
