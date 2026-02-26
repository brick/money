<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Exception\InvalidArgumentException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Formatter\MoneyLocaleFormatter;
use Override;

use function array_fill;
use function array_map;
use function array_values;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * A monetary value in a given currency. This class is immutable.
 *
 * Money has an amount, a currency, and a context. The context defines the scale of the amount, and an optional cash
 * rounding step, for monies that do not have coins or notes for their smallest units.
 *
 * All operations on a Money return another Money with the same context. The available contexts are:
 *
 * - DefaultContext handles monies with the default scale for the currency.
 * - CashContext is similar to DefaultContext, but supports a cash rounding step.
 * - CustomContext handles monies with a custom scale and optionally step.
 * - AutoContext automatically adjusts the scale of the money to the minimum required.
 */
final readonly class Money extends AbstractMoney
{
    /**
     * @param BigDecimal $amount   The amount.
     * @param Currency   $currency The currency.
     * @param Context    $context  The context that defines the capability of this Money.
     *
     * @pure
     */
    private function __construct(
        private BigDecimal $amount,
        private Currency $currency,
        private Context $context,
    ) {
    }

    /**
     * Returns the minimum of the given monies.
     *
     * If several monies are equal to the minimum value, the first one is returned.
     *
     * @param Money $money     The first money.
     * @param Money ...$monies The subsequent monies.
     *
     * @throws MoneyMismatchException If all the monies are not in the same currency.
     *
     * @pure
     */
    public static function min(Money $money, Money ...$monies): Money
    {
        $min = $money;

        foreach ($monies as $money) {
            if ($money->isLessThan($min)) {
                $min = $money;
            }
        }

        return $min;
    }

    /**
     * Returns the maximum of the given monies.
     *
     * If several monies are equal to the maximum value, the first one is returned.
     *
     * @param Money $money     The first money.
     * @param Money ...$monies The subsequent monies.
     *
     * @throws MoneyMismatchException If all the monies are not in the same currency.
     *
     * @pure
     */
    public static function max(Money $money, Money ...$monies): Money
    {
        $max = $money;

        foreach ($monies as $money) {
            if ($money->isGreaterThan($max)) {
                $max = $money;
            }
        }

        return $max;
    }

    /**
     * Returns the sum of the given monies.
     *
     * The monies must share the same currency and context.
     *
     * @param Money $money     The first money.
     * @param Money ...$monies The subsequent monies.
     *
     * @throws MoneyMismatchException If all the monies are not in the same currency and context.
     *
     * @pure
     */
    public static function sum(Money $money, Money ...$monies): Money
    {
        $sum = $money;

        foreach ($monies as $money) {
            $sum = $sum->plus($money);
        }

        return $sum;
    }

    /**
     * Returns a Money of the given amount and currency.
     *
     * By default, the money is created with a DefaultContext. This means that the amount is scaled to match the
     * currency's default fraction digits. For example, `Money::of('2.5', 'USD')` will yield `USD 2.50`.
     * If the amount cannot be safely converted to this scale, an exception is thrown.
     *
     * To override this behaviour, a Context instance can be provided.
     * Operations on this Money return a Money with the same context.
     *
     * @param BigNumber|int|string $amount       The monetary amount.
     * @param Currency|string      $currency     The Currency instance or ISO currency code.
     * @param Context|null         $context      An optional Context, defaults to DefaultContext.
     * @param RoundingMode         $roundingMode An optional RoundingMode, if the amount does not fit the context.
     *
     * @throws MathException              If the amount is not a valid number.
     * @throws UnknownCurrencyException   If the currency is an unknown currency code.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::Unnecessary, and rounding is necessary
     *                                    to represent the amount at the requested scale.
     *
     * @pure
     */
    public static function of(
        BigNumber|int|string $amount,
        Currency|string $currency,
        ?Context $context = new DefaultContext(),
        RoundingMode $roundingMode = RoundingMode::Unnecessary,
    ): Money {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        if ($context === null) {
            trigger_error(
                'Passing null for the $context parameter to Money::of() is deprecated, use named arguments to skip to rounding mode.',
                E_USER_DEPRECATED,
            );

            $context = new DefaultContext();
        }

        $amount = BigNumber::of($amount);

        return self::create($amount, $currency, $context, $roundingMode);
    }

    /**
     * Returns a Money from a number of minor units.
     *
     * By default, the money is created with a DefaultContext. This means that the amount is scaled to match the
     * currency's default fraction digits. For example, `Money::ofMinor(1234, 'USD')` will yield `USD 12.34`.
     * If the amount cannot be safely converted to this scale, an exception is thrown.
     *
     * @param BigNumber|int|string $minorAmount  The amount, in minor currency units.
     * @param Currency|string      $currency     The Currency instance or ISO currency code.
     * @param Context|null         $context      An optional Context, defaults to DefaultContext.
     * @param RoundingMode         $roundingMode An optional RoundingMode, if the amount does not fit the context.
     *
     * @throws MathException              If the amount is not a valid number.
     * @throws UnknownCurrencyException   If the currency is an unknown currency code.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::Unnecessary, and rounding is necessary
     *                                    to represent the amount at the requested scale.
     *
     * @pure
     */
    public static function ofMinor(
        BigNumber|int|string $minorAmount,
        Currency|string $currency,
        ?Context $context = new DefaultContext(),
        RoundingMode $roundingMode = RoundingMode::Unnecessary,
    ): Money {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        if ($context === null) {
            trigger_error(
                'Passing null for the $context parameter to Money::ofMinor() is deprecated, use named arguments to skip to rounding mode.',
                E_USER_DEPRECATED,
            );

            $context = new DefaultContext();
        }

        $divisor = BigInteger::ten()->power($currency->getDefaultFractionDigits());
        $amount = BigRational::of($minorAmount)->dividedBy($divisor);

        return self::create($amount, $currency, $context, $roundingMode);
    }

    /**
     * Returns a Money with zero value, in the given currency.
     *
     * By default, the money is created with a DefaultContext: it has the default scale for the currency.
     * A Context instance can be provided to override the default.
     *
     * @param Currency|string $currency The Currency instance or ISO currency code.
     * @param Context|null    $context  An optional context, defaults to DefaultContext.
     *
     * @pure
     */
    public static function zero(Currency|string $currency, ?Context $context = new DefaultContext()): Money
    {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        if ($context === null) {
            trigger_error(
                'Passing null for the $context parameter to Money::zero() is deprecated, use named arguments to skip to rounding mode.',
                E_USER_DEPRECATED,
            );

            $context = new DefaultContext();
        }

        $amount = BigDecimal::zero();

        return self::create($amount, $currency, $context);
    }

    #[Override]
    public function getAmount(): BigDecimal
    {
        return $this->amount;
    }

    /**
     * Returns the amount of this Money in minor units (cents) for the currency.
     *
     * The value is returned as a BigDecimal. If this Money has a scale greater than that of the currency, the result
     * will have a non-zero scale.
     *
     * For example, `USD 1.23` will return a BigDecimal of `123`, while `USD 1.2345` will return `123.45`.
     *
     * @pure
     */
    public function getMinorAmount(): BigDecimal
    {
        return $this->amount->withPointMovedRight($this->currency->getDefaultFractionDigits());
    }

    /**
     * Returns a BigInteger containing the unscaled value (all digits) of this money.
     *
     * For example, `123.4567 USD` will return a BigInteger of `1234567`.
     *
     * @deprecated Use getAmount()->getUnscaledValue() instead.
     *
     * @pure
     */
    public function getUnscaledAmount(): BigInteger
    {
        trigger_error(
            'Money::getUnscaledAmount() is deprecated, and will be removed in a future version. ' .
            'Use getAmount()->getUnscaledValue() instead.',
            E_USER_DEPRECATED,
        );

        return $this->amount->getUnscaledValue();
    }

    #[Override]
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * Returns the Context of this Money.
     *
     * @pure
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * Returns the sum of this Money and the given amount.
     *
     * If the operand is a Money, it must have the same context as this Money, or an exception is thrown.
     * This is by design, to ensure that contexts are not mixed accidentally.
     * If you do need to add a Money in a different context, you can use `plus($money->toRational())`.
     *
     * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
     * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
     * thrown.
     *
     * @param AbstractMoney|BigNumber|int|string $that         The money or amount to add.
     * @param RoundingMode                       $roundingMode An optional RoundingMode constant.
     *
     * @throws MathException          If the argument is an invalid number or rounding is necessary.
     * @throws MoneyMismatchException If the argument is a money in a different currency or in a different context.
     *
     * @pure
     */
    public function plus(AbstractMoney|BigNumber|int|string $that, RoundingMode $roundingMode = RoundingMode::Unnecessary): Money
    {
        $amount = $this->getAmountOf($that);

        if ($that instanceof Money) {
            $this->checkContext($that->getContext(), __FUNCTION__);
            $amount = $this->amount->plus($amount);
        } else {
            $amount = $this->amount->toBigRational()->plus($amount);
        }

        return self::create($amount, $this->currency, $this->context, $roundingMode);
    }

    /**
     * Returns the difference of this Money and the given amount.
     *
     * If the operand is a Money, it must have the same context as this Money, or an exception is thrown.
     * This is by design, to ensure that contexts are not mixed accidentally.
     * If you do need to subtract a Money in a different context, you can use `minus($money->toRational())`.
     *
     * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
     * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
     * thrown.
     *
     * @param AbstractMoney|BigNumber|int|string $that         The money or amount to subtract.
     * @param RoundingMode                       $roundingMode An optional RoundingMode constant.
     *
     * @throws MathException          If the argument is an invalid number or rounding is necessary.
     * @throws MoneyMismatchException If the argument is a money in a different currency or in a different context.
     *
     * @pure
     */
    public function minus(AbstractMoney|BigNumber|int|string $that, RoundingMode $roundingMode = RoundingMode::Unnecessary): Money
    {
        $amount = $this->getAmountOf($that);

        if ($that instanceof Money) {
            $this->checkContext($that->getContext(), __FUNCTION__);
            $amount = $this->amount->minus($amount);
        } else {
            $amount = $this->amount->toBigRational()->minus($amount);
        }

        return self::create($amount, $this->currency, $this->context, $roundingMode);
    }

    /**
     * Returns the product of this Money and the given number.
     *
     * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
     * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
     * thrown.
     *
     * @param BigNumber|int|string $that         The multiplier.
     * @param RoundingMode         $roundingMode An optional RoundingMode constant.
     *
     * @throws MathException If the argument is an invalid number or rounding is necessary.
     *
     * @pure
     */
    public function multipliedBy(BigNumber|int|string $that, RoundingMode $roundingMode = RoundingMode::Unnecessary): Money
    {
        $amount = $this->amount->toBigRational()->multipliedBy($that);

        return self::create($amount, $this->currency, $this->context, $roundingMode);
    }

    /**
     * Returns the result of the division of this Money by the given number.
     *
     * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
     * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
     * thrown.
     *
     * @param BigNumber|int|string $that         The divisor.
     * @param RoundingMode         $roundingMode An optional RoundingMode constant.
     *
     * @throws MathException If the argument is an invalid number or is zero, or rounding is necessary.
     *
     * @pure
     */
    public function dividedBy(BigNumber|int|string $that, RoundingMode $roundingMode = RoundingMode::Unnecessary): Money
    {
        $amount = $this->amount->toBigRational()->dividedBy($that);

        return self::create($amount, $this->currency, $this->context, $roundingMode);
    }

    /**
     * Returns the quotient of the division of this Money by the given number.
     *
     * The given number must be a integer value. The resulting Money has the same context as this Money.
     *
     * @param BigNumber|int|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @throws MathException If the divisor cannot be converted to a BigInteger.
     *
     * @pure
     */
    public function quotient(BigNumber|int|string $that): Money
    {
        $that = BigInteger::of($that);
        $step = $this->context->getStep();

        $scale = $this->amount->getScale();
        $amount = $this->amount->withPointMovedRight($scale)->dividedBy($step, 0);

        $q = $amount->quotient($that);
        $q = $q->multipliedBy($step)->withPointMovedLeft($scale);

        return new Money($q, $this->currency, $this->context);
    }

    /**
     * Returns the remainder of the division of this Money by the given number.
     *
     * The given number must be an integer value. The resulting Money has the same context as this Money.
     *
     * @param BigNumber|int|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @throws MathException If the divisor cannot be converted to a BigInteger.
     *
     * @pure
     */
    public function remainder(BigNumber|int|string $that): Money
    {
        $that = BigInteger::of($that);
        $step = $this->context->getStep();

        $scale = $this->amount->getScale();
        $amount = $this->amount->withPointMovedRight($scale)->dividedBy($step, 0);

        $r = $amount->remainder($that);
        $r = $r->multipliedBy($step)->withPointMovedLeft($scale);

        return new Money($r, $this->currency, $this->context);
    }

    /**
     * Returns the quotient and the remainder of the division of this Money by the given number.
     *
     * The given number must be an integer value. The resulting monies have the same context as this Money.
     *
     * @param BigNumber|int|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @return array{Money, Money} The quotient and the remainder.
     *
     * @throws MathException If the divisor cannot be converted to a BigInteger.
     *
     * @pure
     */
    public function quotientAndRemainder(BigNumber|int|string $that): array
    {
        $that = BigInteger::of($that);
        $step = $this->context->getStep();

        $scale = $this->amount->getScale();
        $amount = $this->amount->withPointMovedRight($scale)->dividedBy($step, 0);

        [$q, $r] = $amount->quotientAndRemainder($that);

        $q = $q->multipliedBy($step)->withPointMovedLeft($scale);
        $r = $r->multipliedBy($step)->withPointMovedLeft($scale);

        $quotient = new Money($q, $this->currency, $this->context);
        $remainder = new Money($r, $this->currency, $this->context);

        return [$quotient, $remainder];
    }

    /**
     * Allocates this Money according to a list of ratios.
     *
     * If the allocation yields a remainder, its amount is split over the first monies in the list,
     * so that the total of the resulting monies is always equal to this Money.
     *
     * For example, given a `USD 49.99` money in the default context,
     * `allocate(1, 2, 3, 4)` returns [`USD 5.00`, `USD 10.00`, `USD 15.00`, `USD 19.99`]
     *
     * The resulting monies have the same context as this Money.
     *
     * @param BigNumber|int|string ...$ratios The ratios. Must be non-negative and sum to a non-zero value.
     *
     * @return Money[]
     *
     * @throws InvalidArgumentException If called with invalid parameters.
     *
     * @pure
     */
    public function allocate(BigNumber|int|string ...$ratios): array
    {
        $ratios = $this->normalizeRatios($ratios, __FUNCTION__);
        $total = BigInteger::sum(...$ratios);

        $step = $this->context->getStep();

        $monies = [];

        $unit = BigDecimal::ofUnscaledValue($step, $this->amount->getScale());
        $unit = new Money($unit, $this->currency, $this->context);

        if ($this->isNegative()) {
            $unit = $unit->negated();
        }

        $remainder = $this;

        foreach ($ratios as $ratio) {
            $money = $this->multipliedBy($ratio)->quotient($total);
            $remainder = $remainder->minus($money);
            $monies[] = $money;
        }

        foreach ($monies as $key => $money) {
            if ($remainder->isZero()) {
                break;
            }

            $monies[$key] = $money->plus($unit);
            $remainder = $remainder->minus($unit);
        }

        return $monies;
    }

    /**
     * Allocates this Money according to a list of ratios.
     *
     * The remainder is also present, appended at the end of the list.
     *
     * For example, given a `USD 49.99` money in the default context,
     * `allocateWithRemainder(1, 2, 3, 4)` returns [`USD 4.99`, `USD 9.98`, `USD 14.97`, `USD 19.96`, `USD 0.09`]
     *
     * The resulting monies have the same context as this Money.
     *
     * @param BigNumber|int|string ...$ratios The ratios. Must be non-negative and sum to a non-zero value.
     *
     * @return Money[]
     *
     * @throws InvalidArgumentException If called with invalid parameters.
     *
     * @pure
     */
    public function allocateWithRemainder(BigNumber|int|string ...$ratios): array
    {
        $ratios = $this->normalizeRatios($ratios, __FUNCTION__);
        $total = BigInteger::sum(...$ratios);

        [, $remainder] = $this->quotientAndRemainder($total);

        $toAllocate = $this->minus($remainder);

        $monies = array_map(
            fn (BigInteger $ratio) => $toAllocate->multipliedBy($ratio)->dividedBy($total),
            $ratios,
        );

        $monies[] = $remainder;

        return $monies;
    }

    /**
     * Splits this Money into a number of parts.
     *
     * If the division of this Money by the number of parts yields a remainder, its amount is split over the first
     * monies in the list, so that the total of the resulting monies is always equal to this Money.
     *
     * For example, given a `USD 100.00` money in the default context,
     * `split(3)` returns [`USD 33.34`, `USD 33.33`, `USD 33.33`]
     *
     * The resulting monies have the same context as this Money.
     *
     * @param positive-int $parts The number of parts.
     *
     * @return Money[]
     *
     * @throws InvalidArgumentException If called with invalid parameters.
     *
     * @pure
     */
    public function split(int $parts): array
    {
        /** @phpstan-ignore smaller.alwaysFalse */
        if ($parts < 1) {
            throw InvalidArgumentException::splitTooFewParts(__FUNCTION__);
        }

        return $this->allocate(...array_fill(0, $parts, 1));
    }

    /**
     * Splits this Money into a number of parts and a remainder.
     *
     * For example, given a `USD 100.00` money in the default context,
     * `splitWithRemainder(3)` returns [`USD 33.33`, `USD 33.33`, `USD 33.33`, `USD 0.01`]
     *
     * The resulting monies have the same context as this Money.
     *
     * @param positive-int $parts The number of parts.
     *
     * @return Money[]
     *
     * @throws InvalidArgumentException If called with invalid parameters.
     *
     * @pure
     */
    public function splitWithRemainder(int $parts): array
    {
        /** @phpstan-ignore smaller.alwaysFalse */
        if ($parts < 1) {
            throw InvalidArgumentException::splitTooFewParts(__FUNCTION__);
        }

        return $this->allocateWithRemainder(...array_fill(0, $parts, 1));
    }

    /**
     * Returns a Money whose value is the absolute value of this Money.
     *
     * The resulting Money has the same context as this Money.
     */
    #[Override]
    public function abs(): static
    {
        return new Money($this->amount->abs(), $this->currency, $this->context);
    }

    /**
     * Returns a Money whose value is the negated value of this Money.
     *
     * The resulting Money has the same context as this Money.
     */
    #[Override]
    public function negated(): static
    {
        return new Money($this->amount->negated(), $this->currency, $this->context);
    }

    /**
     * Converts this Money to another currency, using an exchange rate.
     *
     * By default, the resulting Money has the same context as this Money.
     * This can be overridden by providing a Context.
     *
     * For example, converting a default money of `USD 1.23` to `EUR` with an exchange rate of `0.91` and
     * RoundingMode::Up will yield `EUR 1.12`.
     *
     * @param Currency|string      $currency     The Currency instance or ISO currency code.
     * @param BigNumber|int|string $exchangeRate The exchange rate to multiply by.
     * @param Context|null         $context      An optional context, defaults to DefaultContext.
     * @param RoundingMode         $roundingMode An optional rounding mode.
     *
     * @throws UnknownCurrencyException If an unknown currency code is given.
     * @throws MathException            If the exchange rate or rounding mode is invalid, or rounding is necessary.
     *
     * @pure
     */
    public function convertedTo(
        Currency|string $currency,
        BigNumber|int|string $exchangeRate,
        ?Context $context = null,
        RoundingMode $roundingMode = RoundingMode::Unnecessary,
    ): Money {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        if ($context === null) {
            trigger_error(
                'Passing null for the $context parameter to Money::convertedTo() is deprecated, use an explicit Context instance. ' .
                'This parameter will default to DefaultContext in a future version.',
                E_USER_DEPRECATED,
            );

            $context = $this->context;
        }

        $amount = $this->amount->toBigRational()->multipliedBy($exchangeRate);

        return self::create($amount, $currency, $context, $roundingMode);
    }

    /**
     * Formats this Money to the given locale.
     *
     * Note that this method uses MoneyLocaleFormatter, which in turn internally uses NumberFormatter, which represents values using floating
     * point arithmetic, so discrepancies can appear when formatting very large monetary values.
     *
     * @param string $locale           The locale to format to, for example 'fr_FR' or 'en_US'.
     * @param bool   $allowWholeNumber Whether to allow formatting as a whole number if the amount has no fraction.
     *
     * @pure
     */
    public function formatToLocale(string $locale, bool $allowWholeNumber = false): string
    {
        return (new MoneyLocaleFormatter($locale, $allowWholeNumber))->format($this);
    }

    #[Override]
    public function toRational(): RationalMoney
    {
        return new RationalMoney($this->amount->toBigRational(), $this->currency);
    }

    /**
     * Returns a non-localized string representation of this Money, e.g. "EUR 23.00".
     *
     * @pure
     */
    #[Override]
    public function __toString(): string
    {
        return $this->currency . ' ' . $this->amount;
    }

    /**
     * Creates a Money from a rational amount, a currency, and a context.
     *
     * @param BigNumber    $amount       The amount.
     * @param Currency     $currency     The currency.
     * @param Context      $context      The context.
     * @param RoundingMode $roundingMode An optional rounding mode if the amount does not fit the context.
     *
     * @throws RoundingNecessaryException If RoundingMode::Unnecessary is used but rounding is necessary.
     *
     * @pure
     */
    protected static function create(BigNumber $amount, Currency $currency, Context $context, RoundingMode $roundingMode = RoundingMode::Unnecessary): Money
    {
        $amount = $context->applyTo($amount, $currency, $roundingMode);

        return new Money($amount, $currency, $context);
    }

    /**
     * @param Context $context The Context to check against this Money.
     * @param string  $method  The invoked method name.
     *
     * @throws MoneyMismatchException If monies don't match.
     *
     * @pure
     */
    protected function checkContext(Context $context, string $method): void
    {
        if ($this->context != $context) { // non-strict equality on purpose
            throw MoneyMismatchException::contextMismatch($method);
        }
    }

    /**
     * @param (BigNumber|int|string)[] $ratios
     *
     * @return non-empty-list<BigInteger>
     *
     * @pure
     */
    private function normalizeRatios(array $ratios, string $method): array
    {
        if ($ratios === []) {
            throw InvalidArgumentException::allocateEmptyRatios($method);
        }

        $ratios = array_map(
            fn (BigNumber|int|string $ratio) => BigRational::of($ratio),
            array_values($ratios),
        );

        foreach ($ratios as $ratio) {
            if ($ratio->isNegative()) {
                throw InvalidArgumentException::allocateNegativeRatios($method);
            }
        }

        $total = BigRational::sum(...$ratios);

        if ($total->isZero()) {
            throw InvalidArgumentException::allocateAllZeroRatios($method);
        }

        $denominators = array_map(fn (BigRational $ratio) => $ratio->getDenominator(), $ratios);
        $multiplier = BigInteger::lcmAll(...$denominators);

        $ratios = array_map(
            fn (BigRational $ratio) => $ratio->getNumerator()->multipliedBy($multiplier->quotient($ratio->getDenominator())),
            $ratios,
        );

        $gcd = BigInteger::gcdAll(...$ratios);

        return array_map(fn (BigInteger $ratio) => $ratio->quotient($gcd), $ratios);
    }
}
