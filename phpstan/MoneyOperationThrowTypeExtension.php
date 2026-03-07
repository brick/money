<?php

declare(strict_types=1);

namespace Brick\Money\PHPStan;

use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\AbstractMoney;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Brick\Money\MoneyBag;
use Brick\Money\RationalMoney;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;
use function in_array;

/**
 * Narrows the throw type of {@see Money}, {@see RationalMoney}, and {@see MoneyBag} instance methods
 * based on argument types and rounding mode.
 *
 * - When the argument is an {@see AbstractMoney}, {@see NumberFormatException} from parsing cannot occur.
 * - When the argument is a `BigNumber`, `int`, or `numeric-string`, parsing cannot throw and there is no currency check.
 * - When the rounding mode is known to NOT be `Unnecessary`, {@see RoundingNecessaryException} cannot occur.
 * - {@see RationalMoney} operations do not involve rounding, so narrowing eliminates all throws.
 */
final class MoneyOperationThrowTypeExtension implements DynamicMethodThrowTypeExtension
{
    private const COMPARISON_METHODS = [
        'compareTo',
        'isEqualTo',
        'isLessThan',
        'isLessThanOrEqualTo',
        'isGreaterThan',
        'isGreaterThanOrEqualTo',
    ];

    private const ARITHMETIC_METHODS = [
        'plus',
        'minus',
        'multipliedBy',
        'dividedBy',
    ];

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $methodName = $methodReflection->getName();
        $className = $methodReflection->getDeclaringClass()->getName();

        if (in_array($methodName, self::COMPARISON_METHODS, true)) {
            return $className === AbstractMoney::class
                || $methodReflection->getDeclaringClass()->isSubclassOf(AbstractMoney::class);
        }

        $isAbstractMoney = $className === AbstractMoney::class
            || $methodReflection->getDeclaringClass()->isSubclassOf(AbstractMoney::class);

        if (in_array($methodName, self::ARITHMETIC_METHODS, true)) {
            return $isAbstractMoney;
        }

        if ($methodName === 'convertedTo') {
            return $className === RationalMoney::class;
        }

        if ($methodName === 'getMoney') {
            return $className === MoneyBag::class;
        }

        return false;
    }

    public function getThrowTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        if ($methodCall->getArgs() === []) {
            return $methodReflection->getThrowType();
        }

        $methodName = $methodReflection->getName();

        if ($methodName === 'convertedTo') {
            return $this->narrowConvertedTo($methodCall, $scope, $methodReflection);
        }

        if ($methodName === 'getMoney') {
            return $this->narrowGetMoney($methodCall, $scope, $methodReflection);
        }

        if (in_array($methodName, self::COMPARISON_METHODS, true)) {
            return $this->narrowComparison($methodCall, $scope, $methodReflection);
        }

        return $this->narrowArithmetic($methodName, $methodCall, $scope, $methodReflection);
    }

    /**
     * Narrows comparison methods on {@see AbstractMoney}.
     *
     * AbstractMoney arg → only {@see MoneyMismatchException}; safe number → no throw.
     */
    private function narrowComparison(
        MethodCall $methodCall,
        Scope $scope,
        MethodReflection $methodReflection,
    ): ?Type {
        $argType = $scope->getType($methodCall->getArgs()[0]->value);

        if ((new ObjectType(AbstractMoney::class))->isSuperTypeOf($argType)->yes()) {
            return new ObjectType(MoneyMismatchException::class);
        }

        if (SafeType::isSafeNumber($argType)) {
            return null;
        }

        return $methodReflection->getThrowType();
    }

    /**
     * Narrows arithmetic methods (`plus`, `minus`, `multipliedBy`, `dividedBy`)
     * on {@see Money} and {@see RationalMoney}.
     *
     * Considers both argument type (safe number / {@see AbstractMoney}) and rounding mode.
     */
    private function narrowArithmetic(
        string $methodName,
        MethodCall $methodCall,
        Scope $scope,
        MethodReflection $methodReflection,
    ): ?Type {
        $callerType = $scope->getType($methodCall->var);
        $isRational = (new ObjectType(RationalMoney::class))->isSuperTypeOf($callerType)->yes();

        $args = $methodCall->getArgs();
        $argType = $scope->getType($args[0]->value);

        $argIsSafeNumber = SafeType::isSafeNumber($argType);
        $argIsAbstractMoney = (new ObjectType(AbstractMoney::class))->isSuperTypeOf($argType)->yes();
        $argIsSafe = $argIsSafeNumber || $argIsAbstractMoney;

        $roundingModeIsSafe = false;

        if (! $isRational && isset($args[1])) {
            $roundingModeIsSafe = SafeType::isSafeRoundingMode($scope->getType($args[1]->value));
        }

        if (! $argIsSafe && ! $roundingModeIsSafe) {
            return $methodReflection->getThrowType();
        }

        // RationalMoney: no rounding concern. Safe arg → no throw (except dividedBy zero).
        if ($isRational) {
            if ($argIsAbstractMoney) {
                $residual = [new ObjectType(MoneyMismatchException::class)];

                if ($methodName === 'dividedBy' && ! SafeType::isNonZero($argType)) {
                    $residual[] = new ObjectType(DivisionByZeroException::class);
                }

                return TypeCombinator::union(...$residual);
            }

            if ($argIsSafeNumber) {
                if ($methodName === 'dividedBy' && ! SafeType::isNonZero($argType)) {
                    return new ObjectType(DivisionByZeroException::class);
                }

                return null;
            }

            return $methodReflection->getThrowType();
        }

        // Money: build residual throw types.
        $residualTypes = [];

        // MoneyMismatchException when arg is AbstractMoney (currency/context mismatch).
        if ($argIsAbstractMoney) {
            $residualTypes[] = new ObjectType(MoneyMismatchException::class);
        }

        // NumberFormatException when arg is not safe (parsing risk).
        if (! $argIsSafe) {
            $residualTypes[] = new ObjectType(NumberFormatException::class);
        }

        // RoundingNecessaryException when rounding mode is not safe.
        if (! $roundingModeIsSafe) {
            $residualTypes[] = new ObjectType(RoundingNecessaryException::class);
        }

        // DivisionByZeroException when divisor could be zero.
        if ($methodName === 'dividedBy' && ! SafeType::isNonZero($argType)) {
            $residualTypes[] = new ObjectType(DivisionByZeroException::class);
        }

        if ($residualTypes === []) {
            return null;
        }

        return TypeCombinator::union(...$residualTypes);
    }

    /**
     * Narrows {@see MoneyBag::getMoney()} — safe currency eliminates {@see UnknownCurrencyException}.
     */
    private function narrowGetMoney(
        MethodCall $methodCall,
        Scope $scope,
        MethodReflection $methodReflection,
    ): ?Type {
        $currencyType = $scope->getType($methodCall->getArgs()[0]->value);

        if (SafeType::isSafeCurrency($currencyType)) {
            return null;
        }

        return $methodReflection->getThrowType();
    }

    /**
     * Narrows {@see RationalMoney::convertedTo()} based on currency and exchange rate argument types.
     */
    private function narrowConvertedTo(
        MethodCall $methodCall,
        Scope $scope,
        MethodReflection $methodReflection,
    ): ?Type {
        $args = $methodCall->getArgs();

        if (count($args) < 2) {
            return $methodReflection->getThrowType();
        }

        $currencyIsSafe = SafeType::isSafeCurrency($scope->getType($args[0]->value));
        $rateIsSafe = SafeType::isSafeNumber($scope->getType($args[1]->value));

        if (! $currencyIsSafe && ! $rateIsSafe) {
            return $methodReflection->getThrowType();
        }

        $residualTypes = [];

        if (! $currencyIsSafe) {
            $residualTypes[] = new ObjectType(UnknownCurrencyException::class);
        }

        if (! $rateIsSafe) {
            $residualTypes[] = new ObjectType(NumberFormatException::class);
        }

        if ($residualTypes === []) {
            return null;
        }

        return TypeCombinator::union(...$residualTypes);
    }
}
