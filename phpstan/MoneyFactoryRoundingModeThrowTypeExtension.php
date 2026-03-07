<?php

declare(strict_types=1);

namespace Brick\Money\PHPStan;

use Brick\Math\Exception\NumberFormatException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Narrows the throw type of {@see Money::of()} and {@see Money::ofMinor()} based on the {@see RoundingMode} parameter.
 *
 * When the rounding mode is known to NOT be `Unnecessary`, {@see RoundingNecessaryException} cannot occur.
 */
final class MoneyFactoryRoundingModeThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{
    /** method name => rounding mode arg index */
    private const METHODS = [
        'of' => 3,
        'ofMinor' => 3,
    ];

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getDeclaringClass()->getName() === Money::class
            && isset(self::METHODS[$methodReflection->getName()]);
    }

    public function getThrowTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): ?Type {
        $methodName = $methodReflection->getName();
        $roundingModeArgIndex = self::METHODS[$methodName];

        $args = $methodCall->getArgs();

        if (! isset($args[$roundingModeArgIndex])) {
            return $methodReflection->getThrowType();
        }

        $roundingModeType = $scope->getType($args[$roundingModeArgIndex]->value);

        if (! SafeType::isSafeRoundingMode($roundingModeType)) {
            return $methodReflection->getThrowType();
        }

        // Rounding mode is not Unnecessary — RoundingNecessaryException cannot occur.
        return TypeCombinator::union(
            new ObjectType(NumberFormatException::class),
            new ObjectType(UnknownCurrencyException::class),
        );
    }
}
