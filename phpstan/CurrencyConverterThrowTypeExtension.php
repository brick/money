<?php

declare(strict_types=1);

namespace Brick\Money\PHPStan;

use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\CurrencyConverter;
use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\Exception\UnknownCurrencyException;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Narrows the throw type of {@see CurrencyConverter::convert()} and {@see CurrencyConverter::convertToRational()}.
 *
 * - When the currency is a `Currency` instance or a known ISO code, {@see UnknownCurrencyException} cannot occur.
 * - When the rounding mode is not `Unnecessary`, {@see RoundingNecessaryException} cannot occur.
 */
final class CurrencyConverterThrowTypeExtension implements DynamicMethodThrowTypeExtension
{
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $className = $methodReflection->getDeclaringClass()->getName();
        $methodName = $methodReflection->getName();

        return $className === CurrencyConverter::class
            && ($methodName === 'convert' || $methodName === 'convertToRational');
    }

    public function getThrowTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        $args = $methodCall->getArgs();

        if (! isset($args[1])) {
            return $methodReflection->getThrowType();
        }

        $methodName = $methodReflection->getName();
        $currencyType = $scope->getType($args[1]->value);
        $currencyIsSafe = SafeType::isSafeCurrency($currencyType);

        // Check rounding mode for convert() — arg index 3.
        $roundingModeIsSafe = false;

        if ($methodName === 'convert' && isset($args[3])) {
            $roundingModeIsSafe = SafeType::isSafeRoundingMode($scope->getType($args[3]->value));
        }

        if (! $currencyIsSafe && ! $roundingModeIsSafe) {
            return $methodReflection->getThrowType();
        }

        $residualTypes = [new ObjectType(CurrencyConversionException::class)];

        if (! $currencyIsSafe) {
            $residualTypes[] = new ObjectType(UnknownCurrencyException::class);
        }

        if ($methodName === 'convert' && ! $roundingModeIsSafe) {
            $residualTypes[] = new ObjectType(RoundingNecessaryException::class);
        }

        return TypeCombinator::union(...$residualTypes);
    }
}
