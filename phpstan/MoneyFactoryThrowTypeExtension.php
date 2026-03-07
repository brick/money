<?php

declare(strict_types=1);

namespace Brick\Money\PHPStan;

use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Brick\Money\RationalMoney;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;
use function in_array;

/**
 * Narrows the throw type of {@see Money::of()}, {@see Money::ofMinor()}, {@see Money::zero()},
 * {@see RationalMoney::of()}, and {@see RationalMoney::zero()}.
 *
 * - When the amount is a `BigNumber`, `int`, or `numeric-string`, {@see NumberFormatException} from parsing cannot occur.
 * - When the currency is a {@see Currency} instance or a known ISO currency code, {@see UnknownCurrencyException} cannot occur.
 * - For `Money::of()`/`ofMinor()`, {@see RoundingNecessaryException} may still occur depending on the rounding mode.
 */
final class MoneyFactoryThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{
    /** @var array<class-string, list<string>> */
    private const SUPPORTED_METHODS = [
        Money::class => ['of', 'ofMinor', 'zero'],
        RationalMoney::class => ['of', 'zero'],
    ];

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        $className = $methodReflection->getDeclaringClass()->getName();
        $methodName = $methodReflection->getName();

        return isset(self::SUPPORTED_METHODS[$className])
            && in_array($methodName, self::SUPPORTED_METHODS[$className], true);
    }

    public function getThrowTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): ?Type {
        $args = $methodCall->getArgs();
        $methodName = $methodReflection->getName();

        // zero() only has a currency parameter.
        if ($methodName === 'zero') {
            return $this->narrowZero($methodCall, $scope, $methodReflection);
        }

        if (count($args) < 2) {
            return $methodReflection->getThrowType();
        }

        $className = $methodReflection->getDeclaringClass()->getName();
        $amountType = $scope->getType($args[0]->value);
        $currencyType = $scope->getType($args[1]->value);

        $amountIsSafe = SafeType::isSafeNumber($amountType);
        $currencyIsSafe = SafeType::isSafeCurrency($currencyType);

        if (! $amountIsSafe && ! $currencyIsSafe) {
            return $methodReflection->getThrowType();
        }

        $residualTypes = [];

        if (! $amountIsSafe) {
            $residualTypes[] = new ObjectType(NumberFormatException::class);
        }

        if (! $currencyIsSafe) {
            $residualTypes[] = new ObjectType(UnknownCurrencyException::class);
        }

        // Money::of()/ofMinor() can still throw RoundingNecessaryException.
        if ($className === Money::class) {
            $residualTypes[] = new ObjectType(RoundingNecessaryException::class);
        }

        if ($residualTypes === []) {
            return null;
        }

        return TypeCombinator::union(...$residualTypes);
    }

    private function narrowZero(
        StaticCall $methodCall,
        Scope $scope,
        MethodReflection $methodReflection,
    ): ?Type {
        $args = $methodCall->getArgs();

        if (count($args) < 1) {
            return $methodReflection->getThrowType();
        }

        $currencyType = $scope->getType($args[0]->value);

        if (SafeType::isSafeCurrency($currencyType)) {
            return null;
        }

        return $methodReflection->getThrowType();
    }
}
