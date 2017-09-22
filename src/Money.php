<?php

namespace Brick\Money;

use Brick\Money\Adjustment\CustomScale;
use Brick\Money\Adjustment\DefaultScale;
use Brick\Money\Adjustment\ExactResult;
use Brick\Money\Exception\CurrencyMismatchException;
use Brick\Money\Exception\MoneyParseException;
use Brick\Money\Exception\UnknownCurrencyException;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\ArithmeticException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;

/**
 * A monetary value in a given currency. This class is immutable.
 */
class Money implements MoneyContainer
{
    /**
     * The amount.
     *
     * @var \Brick\Math\BigDecimal
     */
    private $amount;

    /**
     * The currency.
     *
     * @var \Brick\Money\Currency
     */
    private $currency;

    /**
     * The step by which the last digits of the amount can increment.
     *
     * Defaults to 1. Can be set to any multiple of 2 and 5.
     *
     * @var int
     */
    private $step;

    /**
     * @param BigDecimal $amount
     * @param Currency   $currency
     * @param int        $step
     */
    public function __construct(BigDecimal $amount, Currency $currency, $step = 1)
    {
        $this->amount   = $amount;
        $this->currency = $currency;
        $this->step     = (int) $step;
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
     * @throws CurrencyMismatchException If all the monies are not in the same currency.
     */
    public static function min(Money $money, Money ...$monies)
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
     * @throws CurrencyMismatchException If all the monies are not in the same currency.
     */
    public static function max(Money $money, Money ...$monies)
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
     * The number of fraction digits in the resulting Money is the maximum number of fraction digits
     * across all the given monies.
     *
     * @param Money    $money  The first money.
     * @param Money ...$monies The subsequent monies.
     *
     * @return Money
     *
     * @throws CurrencyMismatchException If all the monies are not in the same currency.
     */
    public static function total(Money $money, Money ...$monies)
    {
        $total = $money;

        foreach ($monies as $money) {
            if ($money->getAmount()->scale() > $total->getAmount()->scale()) {
                $total = $money->plus($total);
            } else {
                $total = $total->plus($money);
            }
        }

        return $total;
    }

    /**
     * Returns a Money of the given amount and currency.
     *
     * By default, the amount is scaled to match the currency's default fraction digits.
     * For example, `Money::of('2.5', 'USD')` will yield `USD 2.50`.
     * If the amount cannot be safely converted to this scale, an exception is thrown; this behaviour can be overridden
     * by providing a rounding mode.
     *
     * To create a Money with a custom scale and/or cash rounding step, an Adjustment instance can be provided.
     *
     * @param BigNumber|number|string $amount     The monetary amount.
     * @param Currency|string         $currency   The currency, as a `Currency` object or currency code string.
     * @param Adjustment|int          $adjustment A RoundingMode constant or Adjustment instance.
     *
     * @return Money
     *
     * @throws NumberFormatException      If the amount is a string in a non-supported format.
     * @throws RoundingNecessaryException If the rounding was necessary to represent the amount at the requested scale.
     */
    public static function of($amount, $currency, $adjustment = RoundingMode::UNNECESSARY)
    {
        $currency = Currency::of($currency);

        if (! $adjustment instanceof Adjustment) {
            $adjustment = new DefaultScale($adjustment);
        }

        $amount = BigNumber::of($amount);

        return $adjustment->applyTo($amount, $currency);
    }

    /**
     * Returns a Money from a number of minor units.
     *
     * The result Money has the default scale for the currency.
     *
     * @param BigNumber|number|string $amountMinor The amount in minor units. Must be convertible to a BigInteger.
     * @param Currency|string         $currency    The currency, as a Currency instance or currency code string.
     *
     * @return Money
     *
     * @throws UnknownCurrencyException If the currency is an unknown currency code.
     * @throws ArithmeticException      If the amount cannot be converted to a BigInteger.
     */
    public static function ofMinor($amountMinor, $currency)
    {
        $currency = Currency::of($currency);

        $amount = BigDecimal::ofUnscaledValue($amountMinor, $currency->getDefaultFractionDigits());

        return new Money($amount, $currency);
    }

    /**
     * Parses a string representation of a money as returned by `__toString()`, e.g. "USD 23.00".
     *
     * @param string $string
     *
     * @return Money
     *
     * @throws MoneyParseException      If the parsing fails.
     * @throws UnknownCurrencyException If the currency code is not known.
     */
    public static function parse($string)
    {
        $pos = strrpos($string, ' ');

        if ($pos === false) {
            throw MoneyParseException::invalidFormat($string);
        }

        $currency = substr($string, 0, $pos);
        $amount   = substr($string, $pos + 1);

        $currency = Currency::of($currency);

        try {
            $amount = BigDecimal::of($amount);
        }
        catch (ArithmeticException $e) {
            throw MoneyParseException::wrap($e);
        }

        return new Money($amount, $currency);
    }

    /**
     * Returns a Money with zero value, in the given Currency.
     *
     * By default, the resulting Money has the default scale for the currency.
     * This behaviour can be overridden by providing an Adjustment instance.
     *
     * @param Currency|string $currency   A currency instance or currency code.
     * @param Adjustment|null $adjustment An optional adjustment.
     *
     * @return Money
     */
    public static function zero($currency, Adjustment $adjustment = null)
    {
        $currency = Currency::of($currency);

        if ($adjustment === null) {
            $adjustment = new DefaultScale();
        }

        $amount = BigDecimal::zero();

        return $adjustment->applyTo($amount, $currency);
    }

    /**
     * Returns the amount of this Money, as a BigDecimal.
     *
     * @return BigDecimal
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Returns the Currency of this Money.
     *
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Applies the given adjustment to this money, and returns the result.
     *
     * @param Adjustment $adjustment
     *
     * @return Money
     */
    public function with(Adjustment $adjustment)
    {
        return $adjustment->applyTo($this->amount, $this->currency);
    }

    /**
     * @todo keep? what about step?
     *
     * Returns a Money with this value, and a given number of fraction digits.
     *
     * @param int $fractionDigits The number of fraction digits.
     * @param int $roundingMode   The rounding mode to apply, if necessary.
     *
     * @return Money
     */
    public function withFractionDigits($fractionDigits, $roundingMode = RoundingMode::UNNECESSARY)
    {
        return new Money($this->amount->toScale($fractionDigits, $roundingMode), $this->currency);
    }

    /**
     * @todo keep? what about step?
     *
     * Returns a copy of this Money with this value, and the default number of fraction digits of the currency in use.
     *
     * @param int $roundingMode The rounding mode to apply, if necessary.
     *
     * @return Money
     */
    public function withDefaultFractionDigits($roundingMode = RoundingMode::UNNECESSARY)
    {
        return $this->withFractionDigits($this->currency->getDefaultFractionDigits(), $roundingMode);
    }

    /**
     * Returns the sum of this Money and the given amount.
     *
     * By default, the resulting Money has the same scale and cash rounding step as this Money. If the result needs
     * rounding to fit this scale, a rounding mode can be provided. If a rounding mode is not provided and rounding is
     * necessary, an exception is thrown.
     *
     * If another scale and/or cash rounding step is required as a result, an Adjustment instance can be provided.
     *
     * @param Money|BigNumber|number|string $that       The amount to be added.
     * @param Adjustment|int                $adjustment A RoundingMode constant or Adjustment instance.
     *
     * @return Money
     *
     * @throws ArithmeticException       If the argument is an invalid number or rounding is necessary.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     */
    public function plus($that, $adjustment = RoundingMode::UNNECESSARY)
    {
        if (! $adjustment instanceof Adjustment) {
            $adjustment = $this->getDefaultAdjustment($adjustment);
        }

        $amount = $this->amount->plus($this->handleMoney($that));

        return $adjustment->applyTo($amount, $this->currency);
    }

    /**
     * Returns the difference of this Money and the given amount.
     *
     * By default, the resulting Money has the same scale and cash rounding step as this Money. If the result needs
     * rounding to fit this scale, a rounding mode can be provided. If a rounding mode is not provided and rounding is
     * necessary, an exception is thrown.
     *
     * If another scale and/or cash rounding step is required as a result, an Adjustment instance can be provided.
     *
     * @param Money|BigNumber|number|string $that       The amount to be subtracted.
     * @param Adjustment|int                $adjustment A RoundingMode constant or Adjustment instance.
     *
     * @return Money
     *
     * @throws ArithmeticException       If the argument is an invalid number or rounding is necessary.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     */
    public function minus($that, $adjustment = RoundingMode::UNNECESSARY)
    {
        if (! $adjustment instanceof Adjustment) {
            $adjustment = $this->getDefaultAdjustment($adjustment);
        }

        $amount = $this->amount->minus($this->handleMoney($that));

        return $adjustment->applyTo($amount, $this->currency);
    }

    /**
     * Returns the product of this Money and the given number.
     *
     * By default, the resulting Money has the same scale and cash rounding step as this Money. If the result needs
     * rounding to fit this scale, a rounding mode can be provided. If a rounding mode is not provided and rounding is
     * necessary, an exception is thrown.
     *
     * If another scale and/or cash rounding step is required as a result, an Adjustment instance can be provided.
     *
     * @param BigNumber|number|string $that       The multiplier.
     * @param Adjustment|int          $adjustment A RoundingMode constant or Adjustment instance.
     *
     * @return Money
     *
     * @throws ArithmeticException If the argument is an invalid number or rounding is necessary.
     */
    public function multipliedBy($that, $adjustment = RoundingMode::UNNECESSARY)
    {
        if (! $adjustment instanceof Adjustment) {
            $adjustment = $this->getDefaultAdjustment($adjustment);
        }

        $amount = $this->amount->multipliedBy($that);

        return $adjustment->applyTo($amount, $this->currency);
    }

    /**
     * Returns the result of the division of this Money by the given number.
     *
     * By default, the resulting Money has the same scale and cash rounding step as this Money. If the result needs
     * rounding to fit this scale, a rounding mode can be provided. If a rounding mode is not provided and rounding is
     * necessary, an exception is thrown.
     *
     * If another scale and/or cash rounding step is required as a result, an Adjustment instance can be provided.
     *
     * @param BigNumber|number|string $that       The divisor.
     * @param Adjustment|int          $adjustment A RoundingMode constant or Adjustment instance.
     *
     * @return Money
     *
     * @throws ArithmeticException If the argument is an invalid number or is zero, or rounding is necessary.
     */
    public function dividedBy($that, $adjustment = RoundingMode::UNNECESSARY)
    {
        if (! $adjustment instanceof Adjustment) {
            $adjustment = $this->getDefaultAdjustment($adjustment);
        }

        $amount = $this->amount->toBigRational()->dividedBy($that);

        return $adjustment->applyTo($amount, $this->currency);
    }

    /**
     * Returns a Money whose value is the absolute value of this Money.
     *
     * @return Money
     */
    public function abs()
    {
        return new Money($this->amount->abs(), $this->currency, $this->step);
    }

    /**
     * Returns a Money whose value is the negated value of this Money.
     *
     * @return Money
     */
    public function negated()
    {
        return new Money($this->amount->negated(), $this->currency, $this->step);
    }

    /**
     * Returns whether this Money has zero value.
     *
     * @return bool
     */
    public function isZero()
    {
        return $this->amount->isZero();
    }

    /**
     * Returns whether this Money has a negative value.
     *
     * @return bool
     */
    public function isNegative()
    {
        return $this->amount->isNegative();
    }

    /**
     * Returns whether this Money has a negative or zero value.
     *
     * @return bool
     */
    public function isNegativeOrZero()
    {
        return $this->amount->isNegativeOrZero();
    }

    /**
     * Returns whether this Money has a positive value.
     *
     * @return bool
     */
    public function isPositive()
    {
        return $this->amount->isPositive();
    }

    /**
     * Returns whether this Money has a positive or zero value.
     *
     * @return bool
     */
    public function isPositiveOrZero()
    {
        return $this->amount->isPositiveOrZero();
    }

    /**
     * Compares this Money to the given amount.
     *
     * @param Money|BigNumber|number|string $that
     *
     * @return int [-1, 0, 1] if `$this` is less than, equal to, or greater than `$that`.
     *
     * @throws ArithmeticException       If the argument is an invalid number.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     */
    public function compareTo($that)
    {
        $that = $this->handleMoney($that);

        return $this->amount->compareTo($that);
    }

    /**
     * Returns whether this Money is equal to the given amount.
     *
     * @param Money|BigNumber|number|string $that
     *
     * @return bool
     *
     * @throws ArithmeticException       If the argument is an invalid number.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     */
    public function isEqualTo($that)
    {
        $that = $this->handleMoney($that);

        return $this->amount->isEqualTo($that);
    }

    /**
     * Returns whether this Money is less than the given amount
     *
     * @param Money|BigNumber|number|string $that
     *
     * @return bool
     *
     * @throws ArithmeticException       If the argument is an invalid number.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     */
    public function isLessThan($that)
    {
        $that = $this->handleMoney($that);

        return $this->amount->isLessThan($that);
    }

    /**
     * Returns whether this Money is less than or equal to the given amount.
     *
     * @param Money|BigNumber|number|string $that
     *
     * @return bool
     *
     * @throws ArithmeticException       If the argument is an invalid number.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     */
    public function isLessThanOrEqualTo($that)
    {
        $that = $this->handleMoney($that);

        return $this->amount->isLessThanOrEqualTo($that);
    }

    /**
     * Returns whether this Money is greater than the given amount.
     *
     * @param Money|BigNumber|number|string $that
     *
     * @return bool
     *
     * @throws ArithmeticException       If the argument is an invalid number.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     */
    public function isGreaterThan($that)
    {
        $that = $this->handleMoney($that);

        return $this->amount->isGreaterThan($that);
    }

    /**
     * Returns whether this Money is greater than or equal to the given amount.
     *
     * @param Money|BigNumber|number|string $that
     *
     * @return bool
     *
     * @throws ArithmeticException       If the argument is an invalid number.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     */
    public function isGreaterThanOrEqualTo($that)
    {
        $that = $this->handleMoney($that);

        return $this->amount->isGreaterThanOrEqualTo($that);
    }

    /**
     * Returns a string representing the integral part of the amount of this money.
     *
     * Example: 123.45 will return '123'.
     *
     * @return string
     */
    public function getIntegral()
    {
        return $this->amount->integral();
    }

    /**
     * Returns a string representing the fractional part of the amount of this money.
     *
     * Example: 123.45 will return '45'.
     *
     * @return string
     */
    public function getFraction()
    {
        return $this->amount->fraction();
    }

    /**
     * Returns a string containing the value of this money in minor units.
     *
     * Example: 123.45 USD will return '12345'.
     *
     * @return string
     */
    public function getAmountMinor()
    {
        return $this->amount->unscaledValue();
    }

    /**
     * Returns a copy of this Money converted into another currency.
     *
     * By default, the scale of the result is adjusted to represent the exact converted value.
     * For example, converting `USD 1.23` to `EUR` with an exchange rate of `0.91` will yield `USD 1.1193`.
     *
     * The scale can be adjusted by providing an Adjustment instance.
     *
     * @param Currency|string         $currency     The target currency or currency code.
     * @param BigNumber|number|string $exchangeRate The exchange rate to multiply by.
     * @param Adjustment|null         $adjustment   An optional adjustment.
     *
     * @return Money
     *
     * @throws UnknownCurrencyException If an unknown currency code is given.
     * @throws ArithmeticException      If the exchange rate or rounding mode is invalid, or rounding is necessary.
     */
    public function convertedTo($currency, $exchangeRate, Adjustment $adjustment = null)
    {
        $currency = Currency::of($currency);

        if ($adjustment === null) {
            $adjustment = new ExactResult();
        }

        $amount = $this->amount->toBigRational()->multipliedBy($exchangeRate);

        return $adjustment->applyTo($amount, $currency, $this->amount->scale());
    }

    /**
     * Formats this Money with the given NumberFormatter.
     *
     * Note that NumberFormatter internally represents values using floating point arithmetic,
     * so discrepancies can appear when formatting very large monetary values.
     *
     * @param \NumberFormatter $formatter
     *
     * @return string
     */
    public function formatWith(\NumberFormatter $formatter)
    {
        return $formatter->formatCurrency(
            (string) $this->amount,
            (string) $this->currency
        );
    }

    /**
     * Formats this Money to the given locale.
     *
     * Note that this method uses NumberFormatter, which internally represents values using floating point arithmetic,
     * so discrepancies can appear when formatting very large monetary values.
     *
     * @param string $locale
     *
     * @return string
     */
    public function formatTo($locale)
    {
        return $this->formatWith(new \NumberFormatter($locale, \NumberFormatter::CURRENCY));
    }

    /**
     * Returns a non-localized string representation of this Money, e.g. "EUR 23.00".
     *
     * @return string
     */
    public function __toString()
    {
        return $this->currency . ' ' . $this->amount;
    }

    /**
     * {@inheritdoc}
     */
    public function getMonies()
    {
        return [$this];
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($currency, CurrencyConverter $converter)
    {
        return $converter->convert($this, $currency);
    }

    /**
     * Handles the special case of monies in methods like `plus()`, `minus()`, etc.
     *
     * @param Money|BigNumber|number|string $that
     *
     * @return BigNumber|number|string
     *
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     */
    private function handleMoney($that)
    {
        if ($that instanceof Money) {
            if (! $that->currency->is($this->currency)) {
                throw CurrencyMismatchException::currencyMismatch($this->currency, $that->currency);
            }

            return $that->amount;
        }

        return $that;
    }

    /**
     * @param int $roundingMode
     *
     * @return CustomScale
     */
    private function getDefaultAdjustment($roundingMode)
    {
        return new CustomScale($this->amount->scale(), $this->step, $roundingMode);
    }
}
