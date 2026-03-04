<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Exception\ContextException;
use Brick\Money\Exception\InvalidArgumentException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Formatter\MoneyLocaleFormatter;
use Override;

use function array_fill;
use function array_map;
use function array_values;
use function count;

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
     * @param Context              $context      An optional Context, defaults to DefaultContext.
     * @param RoundingMode         $roundingMode An optional RoundingMode, if the amount does not fit the context.
     *
     * @throws MathException              If the amount is not a valid number.
     * @throws UnknownCurrencyException   If the currency is an unknown currency code.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::Unnecessary, and rounding is necessary
     *                                    to represent the amount at the requested scale.
     * @throws ContextException           If the context does not apply.
     *
     * @pure
     */
    public static function of(
        BigNumber|int|string $amount,
        Currency|string $currency,
        Context $context = new DefaultContext(),
        RoundingMode $roundingMode = RoundingMode::Unnecessary,
    ): Money {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
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
     * @param Context              $context      An optional Context, defaults to DefaultContext.
     * @param RoundingMode         $roundingMode An optional RoundingMode, if the amount does not fit the context.
     *
     * @throws MathException              If the amount is not a valid number.
     * @throws UnknownCurrencyException   If the currency is an unknown currency code.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::Unnecessary, and rounding is necessary
     *                                    to represent the amount at the requested scale.
     * @throws ContextException           If the context does not apply.
     *
     * @pure
     */
    public static function ofMinor(
        BigNumber|int|string $minorAmount,
        Currency|string $currency,
        Context $context = new DefaultContext(),
        RoundingMode $roundingMode = RoundingMode::Unnecessary,
    ): Money {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
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
     * @param Context         $context  An optional context, defaults to DefaultContext.
     *
     * @throws UnknownCurrencyException If the currency is an unknown currency code.
     * @throws ContextException         If the context does not apply.
     *
     * @pure
     */
    public static function zero(Currency|string $currency, Context $context = new DefaultContext()): Money
    {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
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
     * Returns the Currency of this Money.
     */
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
     * @throws MathException              If the argument is an invalid number.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::Unnecessary, and rounding is necessary.
     * @throws MoneyMismatchException     If the argument is a money in a different currency or in a different context.
     * @throws ContextException           If the context does not apply.
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
     * @throws MathException              If the argument is an invalid number.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::Unnecessary, and rounding is necessary.
     * @throws MoneyMismatchException     If the argument is a money in a different currency or in a different context.
     * @throws ContextException           If the context does not apply.
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
     * @throws MathException              If the argument is an invalid number.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::Unnecessary, and rounding is necessary.
     * @throws ContextException           If the context does not apply.
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
     * @throws MathException              If the argument is an invalid number.
     * @throws DivisionByZeroException    If the argument is zero.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::Unnecessary, and rounding is necessary.
     * @throws ContextException           If the context does not apply.
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
     * @throws ContextException        If the context is not fixed (AutoContext).
     * @throws MathException           If the divisor cannot be converted to a BigInteger.
     * @throws DivisionByZeroException If the divisor is zero.
     *
     * @pure
     */
    public function quotient(BigNumber|int|string $that): Money
    {
        if (! $this->context->isFixedScale()) {
            throw ContextException::notFixedContext(__FUNCTION__);
        }

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
     * @throws ContextException        If the context is not fixed (AutoContext).
     * @throws MathException           If the divisor cannot be converted to a BigInteger.
     * @throws DivisionByZeroException If the divisor is zero.
     *
     * @pure
     */
    public function remainder(BigNumber|int|string $that): Money
    {
        if (! $this->context->isFixedScale()) {
            throw ContextException::notFixedContext(__FUNCTION__);
        }

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
     * @throws ContextException        If the context is not fixed (AutoContext).
     * @throws MathException           If the divisor cannot be converted to a BigInteger.
     * @throws DivisionByZeroException If the divisor is zero.
     *
     * @pure
     */
    public function quotientAndRemainder(BigNumber|int|string $that): array
    {
        if (! $this->context->isFixedScale()) {
            throw ContextException::notFixedContext(__FUNCTION__);
        }

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
     * The `$method` parameter controls the allocation algorithm.
     *
     * For example, given a `USD 1.00` money and `$ratios = [2, 3, 1]` in the default context:
     *
     * | Method                                      | Result                                           |
     * |---------------------------------------------|--------------------------------------------------|
     * | `AllocationMethod::FloorToFirst`            | [`USD 0.34`, `USD 0.50`, `USD 0.16`]             |
     * | `AllocationMethod::FloorToLargestRemainder` | [`USD 0.33`, `USD 0.50`, `USD 0.17`]             |
     * | `AllocationMethod::FloorToLargestRatio`     | [`USD 0.33`, `USD 0.51`, `USD 0.16`]             |
     * | `AllocationMethod::FloorSeparate`           | [`USD 0.33`, `USD 0.50`, `USD 0.16`, `USD 0.01`] |
     * | `AllocationMethod::BlockSeparate`           | [`USD 0.32`, `USD 0.48`, `USD 0.16`, `USD 0.04`] |
     *
     * The resulting monies have the same context as this Money.
     *
     * If this money is negative, the absolute value is allocated and the sign is restored on each result:
     * allocating `USD -1.00` by `[1, 2]` with `FloorToFirst` yields `[USD -0.34, USD -0.66]`.
     *
     * @param (BigNumber|int|string)[] $ratios The ratios. Must be non-negative and sum to a non-zero value.
     * @param AllocationMethod         $method Which allocation method to use.
     *
     * @return list<Money> The allocated monies, in the same order as the input ratios.
     *
     * @throws ContextException         If the context is not fixed (AutoContext).
     * @throws MathException            If the ratio list contains an invalid number.
     * @throws InvalidArgumentException If the ratio list is empty, contains negative values, or sums to zero.
     *
     * @pure
     */
    public function allocate(array $ratios, AllocationMethod $method): array
    {
        if (! $this->context->isFixedScale()) {
            throw ContextException::notFixedContext(__FUNCTION__);
        }

        $ratios = $this->normalizeRatios($ratios);

        $amount = $this->amount->abs();
        $scale = $amount->getScale();
        $step = $this->context->getStep();

        $amountInSteps = $amount->getUnscaledValue()->dividedBy($step);
        $stepCounts = $method->getStrategy()->allocate($amountInSteps, $ratios);

        return array_map(
            function (BigInteger $stepCount) use ($scale, $step): Money {
                $amount = BigDecimal::ofUnscaledValue($stepCount->multipliedBy($step), $scale);

                if ($this->amount->isNegative()) {
                    $amount = $amount->negated();
                }

                // The amount already has the correct scale and is step-aligned, and we are in a fixed context.
                // No need to call create() and apply the context here.
                return new Money($amount, $this->currency, $this->context);
            },
            $stepCounts,
        );
    }

    /**
     * Splits this Money into a number of parts.
     *
     * The `$mode` parameter controls how the remainder is handled.
     *
     * For example, given a `USD 1.00` money split in 3 parts in the default context:
     *
     * | Mode                  | Result                                           |
     * |-----------------------|--------------------------------------------------|
     * | `SplitMode::ToFirst`  | [`USD 0.34`, `USD 0.33`, `USD 0.33`]             |
     * | `SplitMode::Separate` | [`USD 0.33`, `USD 0.33`, `USD 0.33`, `USD 0.01`] |
     *
     * The resulting monies have the same context as this Money.
     *
     * If this money is negative, the absolute value is split and the sign is restored on each result:
     * splitting `USD -1.00` into 3 parts with `ToFirst` yields `[USD -0.34, USD -0.33, USD -0.33]`.
     *
     * @param positive-int $parts The number of parts.
     * @param SplitMode    $mode  The split mode.
     *
     * @return list<Money>
     *
     * @throws ContextException         If the context is not fixed (AutoContext).
     * @throws InvalidArgumentException If the number of parts is less than 1.
     *
     * @pure
     */
    public function split(int $parts, SplitMode $mode): array
    {
        if (! $this->context->isFixedScale()) {
            throw ContextException::notFixedContext(__FUNCTION__);
        }

        /** @phpstan-ignore smaller.alwaysFalse */
        if ($parts < 1) {
            throw InvalidArgumentException::splitTooFewParts();
        }

        // With equal ratios [1, 1, …, 1], FloorToLargestRemainder and FloorToLargestRatio produce the same result as
        // FloorToFirst (all remainders and ratios are equal, so ties always break on original index). Likewise,
        // BlockSeparate produces the same result as FloorSeparate. There are therefore only two distinct outcomes.
        $allocationMethod = match ($mode) {
            SplitMode::ToFirst => AllocationMethod::FloorToFirst,
            SplitMode::Separate => AllocationMethod::FloorSeparate,
        };

        return $this->allocate(array_fill(0, $parts, 1), $allocationMethod);
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
     * By default, the resulting Money is created with a DefaultContext.
     * This can be overridden by providing a Context.
     *
     * For example, converting a default money of `USD 1.23` to `EUR` with an exchange rate of `0.91` and
     * RoundingMode::Up will yield `EUR 1.12`.
     *
     * @param Currency|string      $currency     The Currency instance or ISO currency code.
     * @param BigNumber|int|string $exchangeRate The exchange rate to multiply by.
     * @param Context              $context      An optional context, defaults to DefaultContext.
     * @param RoundingMode         $roundingMode An optional rounding mode.
     *
     * @throws UnknownCurrencyException   If an unknown currency code is given.
     * @throws MathException              If the exchange rate is an invalid number.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::Unnecessary, and rounding is necessary.
     * @throws ContextException           If the context does not apply.
     *
     * @pure
     */
    public function convertedTo(
        Currency|string $currency,
        BigNumber|int|string $exchangeRate,
        Context $context = new DefaultContext(),
        RoundingMode $roundingMode = RoundingMode::Unnecessary,
    ): Money {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
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
     * @throws ContextException           If the context does not apply.
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
     * @throws MathException            If the ratio list contains an invalid number.
     * @throws InvalidArgumentException If the ratio list is empty, contains negative values, or sums to zero.
     *
     * @pure
     */
    private function normalizeRatios(array $ratios): array
    {
        if (count($ratios) === 0) {
            throw InvalidArgumentException::allocateEmptyRatios();
        }

        $ratios = array_values($ratios);
        $ratios = array_map(BigRational::of(...), $ratios);

        foreach ($ratios as $ratio) {
            if ($ratio->isNegative()) {
                throw InvalidArgumentException::allocateNegativeRatios();
            }
        }

        $total = BigRational::sum(...$ratios);

        if ($total->isZero()) {
            throw InvalidArgumentException::allocateAllZeroRatios();
        }

        $denominators = array_map(fn (BigRational $ratio) => $ratio->getDenominator(), $ratios);
        $multiplier = BigInteger::lcmAll(...$denominators);

        $ratios = array_map(
            fn (BigRational $ratio) => $ratio->getNumerator()->multipliedBy($multiplier->dividedBy($ratio->getDenominator())),
            $ratios,
        );

        $gcd = BigInteger::gcdAll(...$ratios);

        return array_map(fn (BigInteger $ratio) => $ratio->quotient($gcd), $ratios);
    }
}
