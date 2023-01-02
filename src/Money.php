<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\Context\DefaultContext;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use InvalidArgumentException;

/**
 * A monetary value in a given currency. This class is immutable.
 *
 * A Money has an amount, a currency, and a context. The context defines the scale of the amount, and an optional cash
 * rounding step, for monies that do not have coins or notes for their smallest units.
 *
 * All operations on a Money return another Money with the same context. The available contexts are:
 *
 * - DefaultContext handles monies with the default scale for the currency.
 * - CashContext is similar to DefaultContext, but supports a cash rounding step.
 * - CustomContext handles monies with a custom scale, and optionally step.
 * - AutoContext automatically adjusts the scale of the money to the minimum required.
 */
final class Money extends AbstractMoney
{
    /**
     * The amount.
     */
    private BigDecimal $amount;

    /**
     * The currency.
     */
    private Currency $currency;

    /**
     * The context that defines the capability of this Money.
     */
    private Context $context;

    /**
     * @param BigDecimal $amount
     * @param Currency   $currency
     * @param Context    $context
     */
    private function __construct(BigDecimal $amount, Currency $currency, Context $context)
    {
        $this->amount   = $amount;
        $this->currency = $currency;
        $this->context  = $context;
    }

    /**
     * Returns the minimum of the given monies.
     *
     * If several monies are equal to the minimum value, the first one is returned.
     *
     * @param Money    $money  The first money.
     * @param Money ...$monies The subsequent monies.
     *
     * @return Money
     *
     * @throws MoneyMismatchException If all the monies are not in the same currency.
     */
    public static function min(Money $money, Money ...$monies) : Money
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
     * @param Money    $money  The first money.
     * @param Money ...$monies The subsequent monies.
     *
     * @return Money
     *
     * @throws MoneyMismatchException If all the monies are not in the same currency.
     */
    public static function max(Money $money, Money ...$monies) : Money
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
     * Returns the total of the given monies.
     *
     * The monies must share the same currency and context.
     *
     * @param Money    $money  The first money.
     * @param Money ...$monies The subsequent monies.
     *
     * @return Money
     *
     * @throws MoneyMismatchException If all the monies are not in the same currency and context.
     */
    public static function total(Money $money, Money ...$monies) : Money
    {
        $total = $money;

        foreach ($monies as $money) {
            $total = $total->plus($money);
        }

        return $total;
    }

    /**
     * Creates a Money from a rational amount, a currency, and a context.
     *
     * @psalm-param RoundingMode::* $roundingMode
     *
     * @param BigNumber $amount       The amount.
     * @param Currency  $currency     The currency.
     * @param Context   $context      The context.
     * @param int       $roundingMode An optional rounding mode if the amount does not fit the context.
     *
     * @return Money
     *
     * @throws RoundingNecessaryException If RoundingMode::UNNECESSARY is used but rounding is necessary.
     */
    public static function create(BigNumber $amount, Currency $currency, Context $context, int $roundingMode = RoundingMode::UNNECESSARY) : Money
    {
        $amount = $context->applyTo($amount, $currency, $roundingMode);

        return new Money($amount, $currency, $context);
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
     * @psalm-param RoundingMode::* $roundingMode
     *
     * @param BigNumber|int|float|string $amount       The monetary amount.
     * @param Currency|string|int        $currency     The Currency instance, ISO currency code or ISO numeric currency code.
     * @param Context|null               $context      An optional Context.
     * @param int                        $roundingMode An optional RoundingMode, if the amount does not fit the context.
     *
     * @return Money
     *
     * @throws NumberFormatException      If the amount is a string in a non-supported format.
     * @throws UnknownCurrencyException   If the currency is an unknown currency code.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::UNNECESSARY, and rounding is necessary
     *                                    to represent the amount at the requested scale.
     */
    public static function of(
        BigNumber|int|float|string $amount,
        Currency|string|int $currency,
        ?Context $context = null,
        int $roundingMode = RoundingMode::UNNECESSARY,
    ) : Money {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        if ($context === null) {
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
     * @psalm-param RoundingMode::* $roundingMode
     *
     * @param BigNumber|int|float|string $minorAmount  The amount, in minor currency units.
     * @param Currency|string|int        $currency     The Currency instance, ISO currency code or ISO numeric currency code.
     * @param Context|null               $context      An optional Context.
     * @param int                        $roundingMode An optional RoundingMode, if the amount does not fit the context.
     *
     * @return Money
     *
     * @throws NumberFormatException      If the amount is a string in a non-supported format.
     * @throws UnknownCurrencyException   If the currency is an unknown currency code.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::UNNECESSARY, and rounding is necessary
     *                                    to represent the amount at the requested scale.
     */
    public static function ofMinor(
        BigNumber|int|float|string $minorAmount,
        Currency|string|int $currency,
        ?Context $context = null,
        int $roundingMode = RoundingMode::UNNECESSARY,
    ) : Money {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        if ($context === null) {
            $context = new DefaultContext();
        }

        $amount = BigRational::of($minorAmount)->dividedBy(10 ** $currency->getDefaultFractionDigits());

        return self::create($amount, $currency, $context, $roundingMode);
    }

    /**
     * Returns a Money with zero value, in the given currency.
     *
     * By default, the money is created with a DefaultContext: it has the default scale for the currency.
     * A Context instance can be provided to override the default.
     *
     * @param Currency|string|int $currency The Currency instance, ISO currency code or ISO numeric currency code.
     * @param Context|null        $context  An optional context.
     *
     * @return Money
     */
    public static function zero(Currency|string|int $currency, ?Context $context = null) : Money
    {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        if ($context === null) {
            $context = new DefaultContext();
        }

        $amount = BigDecimal::zero();

        return self::create($amount, $currency, $context);
    }

    /**
     * Returns the amount of this Money, as a BigDecimal.
     *
     * @return BigDecimal
     */
    public function getAmount() : BigDecimal
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
     * @return BigDecimal
     */
    public function getMinorAmount() : BigDecimal
    {
        return $this->amount->withPointMovedRight($this->currency->getDefaultFractionDigits());
    }

    /**
     * Returns a BigInteger containing the unscaled value (all digits) of this money.
     *
     * For example, `123.4567 USD` will return a BigInteger of `1234567`.
     *
     * @return BigInteger
     */
    public function getUnscaledAmount() : BigInteger
    {
        return $this->amount->getUnscaledValue();
    }

    /**
     * Returns the Currency of this Money.
     *
     * @return Currency
     */
    public function getCurrency() : Currency
    {
        return $this->currency;
    }

    /**
     * Returns the Context of this Money.
     *
     * @return Context
     */
    public function getContext() : Context
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
     * @psalm-param RoundingMode::* $roundingMode
     *
     * @param AbstractMoney|BigNumber|int|float|string $that         The money or amount to add.
     * @param int                                      $roundingMode An optional RoundingMode constant.
     *
     * @return Money
     *
     * @throws MathException          If the argument is an invalid number or rounding is necessary.
     * @throws MoneyMismatchException If the argument is a money in a different currency or in a different context.
     */
    public function plus(AbstractMoney|BigNumber|int|float|string $that, int $roundingMode = RoundingMode::UNNECESSARY) : Money
    {
        $amount = $this->getAmountOf($that);

        if ($that instanceof Money) {
            $this->checkContext($that->getContext(), __FUNCTION__);

            if ($this->context->isFixedScale()) {
                return new Money($this->amount->plus($that->amount), $this->currency, $this->context);
            }
        }

        $amount = $this->amount->toBigRational()->plus($amount);

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
     * @psalm-param RoundingMode::* $roundingMode
     *
     * @param AbstractMoney|BigNumber|int|float|string $that         The money or amount to subtract.
     * @param int                                      $roundingMode An optional RoundingMode constant.
     *
     * @return Money
     *
     * @throws MathException          If the argument is an invalid number or rounding is necessary.
     * @throws MoneyMismatchException If the argument is a money in a different currency or in a different context.
     */
    public function minus(AbstractMoney|BigNumber|int|float|string $that, int $roundingMode = RoundingMode::UNNECESSARY) : Money
    {
        $amount = $this->getAmountOf($that);

        if ($that instanceof Money) {
            $this->checkContext($that->getContext(), __FUNCTION__);

            if ($this->context->isFixedScale()) {
                return new Money($this->amount->minus($that->amount), $this->currency, $this->context);
            }
        }

        $amount = $this->amount->toBigRational()->minus($amount);

        return self::create($amount, $this->currency, $this->context, $roundingMode);
    }

    /**
     * Returns the product of this Money and the given number.
     *
     * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
     * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
     * thrown.
     *
     * @psalm-param RoundingMode::* $roundingMode
     *
     * @param BigNumber|int|float|string $that         The multiplier.
     * @param int                        $roundingMode An optional RoundingMode constant.
     *
     * @return Money
     *
     * @throws MathException If the argument is an invalid number or rounding is necessary.
     */
    public function multipliedBy(BigNumber|int|float|string $that, int $roundingMode = RoundingMode::UNNECESSARY) : Money
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
     * @psalm-param RoundingMode::* $roundingMode
     *
     * @param BigNumber|int|float|string $that         The divisor.
     * @param int                        $roundingMode An optional RoundingMode constant.
     *
     * @return Money
     *
     * @throws MathException If the argument is an invalid number or is zero, or rounding is necessary.
     */
    public function dividedBy(BigNumber|int|float|string $that, int $roundingMode = RoundingMode::UNNECESSARY) : Money
    {
        $amount = $this->amount->toBigRational()->dividedBy($that);

        return self::create($amount, $this->currency, $this->context, $roundingMode);
    }

    /**
     * Returns the quotient of the division of this Money by the given number.
     *
     * The given number must be a integer value. The resulting Money has the same context as this Money.
     * This method can serve as a basis for a money allocation algorithm.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @return Money
     *
     * @throws MathException If the divisor cannot be converted to a BigInteger.
     */
    public function quotient(BigNumber|int|float|string $that) : Money
    {
        $that = BigInteger::of($that);
        $step = $this->context->getStep();

        $scale  = $this->amount->getScale();
        $amount = $this->amount->withPointMovedRight($scale)->dividedBy($step);

        $q = $amount->quotient($that);
        $q = $q->multipliedBy($step)->withPointMovedLeft($scale);

        return new Money($q, $this->currency, $this->context);
    }

    /**
     * Returns the quotient and the remainder of the division of this Money by the given number.
     *
     * The given number must be an integer value. The resulting monies have the same context as this Money.
     * This method can serve as a basis for a money allocation algorithm.
     *
     * @param BigNumber|int|float|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @return Money[] The quotient and the remainder.
     *
     * @psalm-return array{Money, Money}
     *
     * @throws MathException If the divisor cannot be converted to a BigInteger.
     */
    public function quotientAndRemainder(BigNumber|int|float|string $that) : array
    {
        $that = BigInteger::of($that);
        $step = $this->context->getStep();

        $scale  = $this->amount->getScale();
        $amount = $this->amount->withPointMovedRight($scale)->dividedBy($step);

        [$q, $r] = $amount->quotientAndRemainder($that);

        $q = $q->multipliedBy($step)->withPointMovedLeft($scale);
        $r = $r->multipliedBy($step)->withPointMovedLeft($scale);

        $quotient  = new Money($q, $this->currency, $this->context);
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
     * @param int[] $ratios The ratios.
     *
     * @return Money[]
     *
     * @throws \InvalidArgumentException If called with invalid parameters.
     */
    public function allocate(int ...$ratios) : array
    {
        if (! $ratios) {
            throw new \InvalidArgumentException('Cannot allocate() an empty list of ratios.');
        }

        foreach ($ratios as $ratio) {
            if ($ratio < 0) {
                throw new \InvalidArgumentException('Cannot allocate() negative ratios.');
            }
        }

        $total = array_sum($ratios);

        if ($total === 0) {
            throw new \InvalidArgumentException('Cannot allocate() to zero ratios only.');
        }

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
     * `allocateWithRemainder(1, 2, 3, 4)` returns [`USD 4.99`, `USD 9.99`, `USD 14.99`, `USD 19.99`, `USD 0.03`]
     *
     * The resulting monies have the same context as this Money.
     *
     * @param int[] $ratios The ratios.
     *
     * @return Money[]
     *
     * @throws \InvalidArgumentException If called with invalid parameters.
     */
    public function allocateWithRemainder(int ...$ratios) : array
    {
        if (! $ratios) {
            throw new \InvalidArgumentException('Cannot allocateWithRemainder() an empty list of ratios.');
        }

        foreach ($ratios as $ratio) {
            if ($ratio < 0) {
                throw new \InvalidArgumentException('Cannot allocateWithRemainder() negative ratios.');
            }
        }

        $total = array_sum($ratios);

        if ($total === 0) {
            throw new \InvalidArgumentException('Cannot allocateWithRemainder() to zero ratios only.');
        }

        $ratios = $this->simplifyRatios(array_values($ratios));
        $total = array_sum($ratios);

        [, $remainder] = $this->quotientAndRemainder($total);

        $toAllocate = $this->minus($remainder);

        $monies = array_map(
            fn (int $ratio) => $toAllocate->multipliedBy($ratio)->dividedBy($total),
            $ratios,
        );

        $monies[] = $remainder;

        return $monies;
    }

    /**
     * @param int[] $ratios
     * @psalm-param non-empty-list<int> $ratios
     *
     * @return int[]
     * @psalm-return non-empty-list<int>
     */
    private function simplifyRatios(array $ratios): array
    {
        $gcd = $this->gcdOfMultipleInt($ratios);

        return array_map(fn (int $ratio) => intdiv($ratio, $gcd), $ratios);
    }

    /**
     * @param int[] $values
     *
     * @psalm-param non-empty-list<int> $values
     */
    private function gcdOfMultipleInt(array $values): int
    {
        $values = array_map(fn (int $value) => BigInteger::of($value), $values);

        return BigInteger::gcdMultiple(...$values)->toInt();
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
     * @param int $parts The number of parts.
     *
     * @return Money[]
     *
     * @throws \InvalidArgumentException If called with invalid parameters.
     */
    public function split(int $parts) : array
    {
        if ($parts < 1) {
            throw new \InvalidArgumentException('Cannot split() into less than 1 part.');
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
     * @param int $parts The number of parts
     *
     * @return Money[]
     *
     * @throws \InvalidArgumentException If called with invalid parameters.
     */
    public function splitWithRemainder(int $parts) : array
    {
        if ($parts < 1) {
            throw new \InvalidArgumentException('Cannot splitWithRemainder() into less than 1 part.');
        }

        return $this->allocateWithRemainder(...array_fill(0, $parts, 1));
    }

    /**
     * Returns a Money whose value is the absolute value of this Money.
     *
     * The resulting Money has the same context as this Money.
     *
     * @return Money
     */
    public function abs() : Money
    {
        return new Money($this->amount->abs(), $this->currency, $this->context);
    }

    /**
     * Returns a Money whose value is the negated value of this Money.
     *
     * The resulting Money has the same context as this Money.
     *
     * @return Money
     */
    public function negated() : Money
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
     * RoundingMode::UP will yield `EUR 1.12`.
     *
     * @psalm-param RoundingMode::* $roundingMode
     *
     * @param Currency|string|int        $currency     The Currency instance, ISO currency code or ISO numeric currency code.
     * @param BigNumber|int|float|string $exchangeRate The exchange rate to multiply by.
     * @param Context|null               $context      An optional context.
     * @param int                        $roundingMode An optional rounding mode.
     *
     * @return Money
     *
     * @throws UnknownCurrencyException If an unknown currency code is given.
     * @throws MathException            If the exchange rate or rounding mode is invalid, or rounding is necessary.
     */
    public function convertedTo(
        Currency|string|int $currency,
        BigNumber|int|float|string $exchangeRate,
        ?Context $context = null,
        int $roundingMode = RoundingMode::UNNECESSARY,
    ) : Money {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        if ($context === null) {
            $context = $this->context;
        }

        $amount = $this->amount->toBigRational()->multipliedBy($exchangeRate);

        return self::create($amount, $currency, $context, $roundingMode);
    }

    /**
     * Formats this Money with the given NumberFormatter.
     *
     * Note that NumberFormatter internally represents values using floating point arithmetic,
     * so discrepancies can appear when formatting very large monetary values.
     *
     * @param \NumberFormatter $formatter The formatter to format with.
     *
     * @return string
     */
    public function formatWith(\NumberFormatter $formatter) : string
    {
        return $formatter->formatCurrency(
            $this->amount->toFloat(),
            $this->currency->getCurrencyCode()
        );
    }

    /**
     * Formats this Money to the given locale.
     *
     * Note that this method uses NumberFormatter, which internally represents values using floating point arithmetic,
     * so discrepancies can appear when formatting very large monetary values.
     *
     * @param string $locale           The locale to format to.
     * @param bool   $allowWholeNumber Whether to allow formatting as a whole number if the amount has no fraction.
     *
     * @return string
     */
    public function formatTo(string $locale, bool $allowWholeNumber = false) : string
    {
        /** @var \NumberFormatter|null $lastFormatter */
        static $lastFormatter = null;
        static $lastFormatterLocale;
        static $lastFormatterScale;

        if ($allowWholeNumber && ! $this->amount->hasNonZeroFractionalPart()) {
            $scale = 0;
        } else {
            $scale = $this->amount->getScale();
        }

        if ($lastFormatter !== null && $lastFormatterLocale === $locale) {
            $formatter = $lastFormatter;

            if ($lastFormatterScale !== $scale) {
                $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $scale);
                $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $scale);

                $lastFormatterScale = $scale;
            }
        } else {
            $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

            $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $scale);
            $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $scale);

            $lastFormatter = $formatter;
            $lastFormatterLocale = $locale;
            $lastFormatterScale = $scale;
        }

        return $this->formatWith($formatter);
    }

    /**
     * @return RationalMoney
     */
    public function toRational() : RationalMoney
    {
        return new RationalMoney($this->amount->toBigRational(), $this->currency);
    }

    /**
     * Returns a non-localized string representation of this Money, e.g. "EUR 23.00".
     */
    public function __toString() : string
    {
        return $this->currency . ' ' . $this->amount;
    }

    /**
     * @param Context $context The Context to check against this Money.
     * @param string  $method  The invoked method name.
     *
     * @return void
     *
     * @throws MoneyMismatchException If monies don't match.
     */
    protected function checkContext(Context $context, string $method) : void
    {
        if ($this->context != $context) { // non-strict equality on purpose
            throw MoneyMismatchException::contextMismatch($method);
        }
    }
}
