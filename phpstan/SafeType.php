<?php

declare(strict_types=1);

namespace Brick\Money\PHPStan;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Currency;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/**
 * Helpers for checking whether argument types are safe to pass without risk of exceptions.
 */
final class SafeType
{
    private static ?Type $safeNumberType = null;

    /** @var array<string, true>|null */
    private static ?array $knownCurrencies = null;

    /**
     * Returns whether the given type can be safely passed to {@see BigNumber::of()} without risk of parsing exceptions.
     *
     * Safe types: `BigNumber` (and subclasses), `int`, `numeric-string`.
     */
    public static function isSafeNumber(Type $type): bool
    {
        return self::getSafeNumberType()->isSuperTypeOf($type)->yes();
    }

    /**
     * Returns whether the given type is a known currency that cannot throw {@see UnknownCurrencyException}.
     *
     * This is true when the type is a {@see Currency} instance, or a constant string matching a known ISO currency code.
     */
    public static function isSafeCurrency(Type $type): bool
    {
        if ((new ObjectType(Currency::class))->isSuperTypeOf($type)->yes()) {
            return true;
        }

        $constantStrings = $type->getConstantStrings();

        if ($constantStrings === []) {
            return false;
        }

        $knownCurrencies = self::getKnownCurrencies();

        foreach ($constantStrings as $constantString) {
            if (! isset($knownCurrencies[$constantString->getValue()])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns whether the given rounding mode type is known to NOT be `Unnecessary`,
     * meaning {@see RoundingNecessaryException} cannot occur.
     */
    public static function isSafeRoundingMode(Type $type): bool
    {
        $unnecessaryType = new EnumCaseObjectType(RoundingMode::class, 'Unnecessary');

        return $unnecessaryType->isSuperTypeOf($type)->no();
    }

    /**
     * Returns whether the given type is guaranteed to be non-zero.
     *
     * This is used to eliminate {@see DivisionByZeroException} from throw types.
     * Only proven for integer types (e.g. int<1, max>, literal 5).
     */
    public static function isNonZero(Type $type): bool
    {
        if (! (new IntegerType())->isSuperTypeOf($type)->yes()) {
            return false;
        }

        $zeroType = new ConstantIntegerType(0);

        return $zeroType->isSuperTypeOf($type)->no();
    }

    private static function getSafeNumberType(): Type
    {
        return self::$safeNumberType ??= new UnionType([
            new ObjectType(BigNumber::class),
            new IntegerType(),
            new IntersectionType([new StringType(), new AccessoryNumericStringType()]),
        ]);
    }

    /**
     * @return array<string, true>
     */
    private static function getKnownCurrencies(): array
    {
        if (self::$knownCurrencies === null) {
            /** @var array<string, mixed> $data */
            $data = require __DIR__ . '/../data/iso-currencies.php';
            self::$knownCurrencies = [];

            foreach ($data as $code => $_) {
                self::$knownCurrencies[$code] = true;
            }
        }

        return self::$knownCurrencies;
    }
}
