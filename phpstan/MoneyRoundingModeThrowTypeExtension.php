<?php

declare(strict_types=1);

namespace Brick\Money\PHPStan;

use Brick\Math\Exception\NumberFormatException;
use Brick\Money\AbstractMoney;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Narrows the throw type of {@see Money::toContext()} and {@see Money::convertedTo()}
 * based on the {@see RoundingMode} parameter.
 *
 * When the rounding mode is known to NOT be `Unnecessary`, {@see RoundingNecessaryException} cannot occur.
 */
final class MoneyRoundingModeThrowTypeExtension implements DynamicMethodThrowTypeExtension
{
    /**
     * method name => rounding mode arg index.
     *
     * @var array<string, int>
     */
    private const METHODS = [
        'toContext' => 1,
        'convertedTo' => 3,
    ];

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $methodName = $methodReflection->getName();
        $className = $methodReflection->getDeclaringClass()->getName();

        if (! isset(self::METHODS[$methodName])) {
            return false;
        }

        return $className === Money::class
            || $className === AbstractMoney::class
            || $methodReflection->getDeclaringClass()->isSubclassOf(AbstractMoney::class);
    }

    public function getThrowTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
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
        if ($methodName === 'toContext') {
            return null;
        }

        // convertedTo: UnknownCurrencyException + NumberFormatException remain.
        return TypeCombinator::union(
            new ObjectType(UnknownCurrencyException::class),
            new ObjectType(NumberFormatException::class),
        );
    }
}
