<?php

declare(strict_types=1);

namespace App\PHPStan;

use Illuminate\Database\ConnectionInterface;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

final class ConnectionTransactionReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return ConnectionInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'transaction';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        if ($methodCall->getArgs() === []) {
            return null;
        }

        $callbackType = $scope->getType($methodCall->getArgs()[0]->value);
        $callbackAcceptor = $callbackType->getCallableParametersAcceptors($scope);

        if ($callbackAcceptor === []) {
            return null;
        }

        return $callbackAcceptor[0]->getReturnType();
    }
}
