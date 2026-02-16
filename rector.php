<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/public',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/bootstrap/cache',
        __DIR__ . '/storage',
        __DIR__ . '/vendor',
        // Skip override attribute for Laravel - causes issues with framework classes
        AddOverrideAttributeToOverriddenMethodsRector::class,
        // Skip aggressive naming rules for migrations (keeps Laravel convention of $table)
        RenameParamToMatchTypeRector::class => [
            __DIR__ . '/database/migrations',
        ],
        // Skip arrow function conversion in Actions (arch test requires full closure in transactions)
        ClosureToArrowFunctionRector::class => [
            __DIR__ . '/app/Actions',
        ],
        // Skip unused parameter removal in Policies (Laravel requires $user and model params by contract)
        RemoveUnusedPublicMethodParameterRector::class => [
            __DIR__ . '/app/Policies',
        ],
        // Skip variable renaming in tests (keeps meaningful mock variable names)
        RenameVariableToMatchMethodCallReturnTypeRector::class => [
            __DIR__ . '/tests',
        ],
        RenameVariableToMatchNewTypeRector::class => [
            __DIR__ . '/tests',
        ],
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        instanceOf: true,
        naming: true,
    )
    ->withSets([
        // Laravel-specific code quality improvements
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
    ])
    ->withImportNames(
        importNames: true,
        importDocBlockNames: true,
        importShortClasses: false,
        removeUnusedImports: true,
    );
