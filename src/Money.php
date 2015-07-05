<?php

namespace Brick\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\CurrencyMismatchException;
use Brick\Money\Exception\MoneyParseException;

/**
 * Represents a monetary value in a given currency. This class is immutable.
 */
class Money
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
     * Private constructor. Use a factory method to obtain an instance.
     *
     * @param BigDecimal $amount   The amount.
     * @param Currency   $currency The currency.
     */
    private function __construct(BigDecimal $amount, Currency $currency)
    {
        $this->amount   = $amount;
        $this->currency = $currency;
    }

    /**
     * Returns the minimum of the given monies.
     *
     * @param Money ...$monies
     *
     * @return Money
     *
     * @throws CurrencyMismatchException If all the monies are not in the same currency.
     * @throws \InvalidArgumentException If the money list is empty.
     */
    public static function min(Money ...$monies)
    {
        $min = null;

        foreach ($monies as $money) {
            if ($min === null || $money->isLessThan($min)) {
                $min = $money;
            }
        }

        if ($min === null) {
            throw new \InvalidArgumentException('min() expects at least one Money.');
        }

        return $min;
    }

    /**
     * Returns the maximum of the given monies.
     *
     * @param Money ...$monies
     *
     * @return Money
     *
     * @throws CurrencyMismatchException If all the monies are not in the same currency.
     * @throws \InvalidArgumentException If the money list is empty.
     */
    public static function max(Money ...$monies)
    {
        $max = null;

        foreach ($monies as $money) {
            if ($max === null || $money->isGreaterThan($max)) {
                $max = $money;
            }
        }

        if ($max === null) {
            throw new \InvalidArgumentException('max() expects at least one Money.');
        }

        return $max;
    }

    /**
     * Returns the total of the given monies.
     *
     * The result Money object has the maximum of the scales of all monies given.
     *
     * @param Money ...$monies
     *
     * @return Money
     *
     * @throws CurrencyMismatchException If all the monies are not in the same currency.
     * @throws \InvalidArgumentException If the money list is empty.
     */
    public static function total(Money ...$monies)
    {
        $total = null;

        foreach ($monies as $money) {
            $total = ($total === null) ? $money : $total->plus($money);
        }

        if ($total === null) {
            throw new \InvalidArgumentException('total() expects at least one Money.');
        }

        return $total;
    }

    /**
     * Returns a Money of the given amount and currency.
     *
     * By default, the amount is scaled to match the currency's default fraction digits.
     * For example, `Money::of('2.5', 'USD')` will yield `USD 2.50`.
     * If amount cannot be converted to this scale, an exception is thrown.
     *
     * This behaviour can be overridden by providing the `$scale` and `$roundingMode` parameters.
     *
     * @param BigNumber|number|string  $amount       The monetary amount.
     * @param Currency|string          $currency     The currency, as a `Currency` object or currency code string.
     * @param int|null                 $scale        The scale, or null to use the currency's default fraction digits.
     * @param int                      $roundingMode The rounding mode to use, if necessary.
     *
     * @return Money
     *
     * @throws NumberFormatException      If the amount is a string in a non-supported format.
     * @throws RoundingNecessaryException If the scale exceeds the currency scale and no rounding is requested.
     */
    public static function of($amount, $currency, $scale = null, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $currency = Currency::of($currency);

        if ($scale === null) {
            $scale = $currency->getDefaultFractionDigits();
        }

        $amount = BigDecimal::of($amount)->toScale($scale, $roundingMode);

        return new Money($amount, $currency);
    }

    /**
     * @param BigNumber|number|string $amountMinor    The integer amount in minor units.
     * @param Currency|string         $currency       The currency, as a Currency instance of currency code string.
     * @param int|null                $fractionDigits The number of fraction digits, or null to use the default.
     *
     * @return Money
     */
    public static function ofMinor($amountMinor, $currency, $fractionDigits = null)
    {
        $currency = Currency::of($currency);

        if ($fractionDigits === null) {
            $fractionDigits = $currency->getDefaultFractionDigits();
        }

        $amount = BigDecimal::ofUnscaledValue($amountMinor, $fractionDigits);

        return new Money($amount, $currency);
    }

    /**
     * Parses a string representation of a money as returned by `__toString()`, e.g. "USD 23.00".
     *
     * @param Money|string $string
     *
     * @return Money
     *
     * @throws MoneyParseException If the parsing fails.
     */
    public static function parse($string)
    {
        if ($string instanceof Money) {
            return $string;
        }

        $parts = explode(' ', $string);

        if (count($parts) != 2) {
            throw MoneyParseException::invalidFormat($string);
        }

        try {
            $currency = Currency::of($parts[0]);
            $amount   = BigDecimal::of($parts[1]);
        }
        catch (\InvalidArgumentException $e) {
            throw MoneyParseException::wrap($e);
        }

        return new Money($amount, $currency);
    }

    /**
     * Returns a Money with zero value, in the given Currency.
     *
     * @param Currency|string $currency A currency instance or currency code.
     * @param int|null        $scale    The scale, or null to use the currency's default fraction digits.
     *
     * @return Money
     */
    public static function zero($currency, $scale = null)
    {
        $currency = Currency::of($currency);

        if ($scale === null) {
            $scale = $currency->getDefaultFractionDigits();
        }

        $amount = BigDecimal::zero()->toScale($scale);

        return new Money($amount, $currency);
    }

    /**
     * @param Currency|string $currency
     *
     * @return void
     *
     * @throws CurrencyMismatchException
     */
    public function checkCurrency($currency)
    {
        $currency = Currency::of($currency);

        if (! $this->currency->is($currency)) {
            throw CurrencyMismatchException::currencyMismatch($this->currency, $currency);
        }
    }

    /**
     * Returns the amount of this Money, as a BigDecimal.
     *
     * @return \Brick\Math\BigDecimal
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
     * Returns a copy of this Money with the given scale.
     *
     * @param int $scale        The scale to apply.
     * @param int $roundingMode The rounding mode to apply, if necessary.
     *
     * @return Money
     */
    public function withScale($scale, $roundingMode = RoundingMode::UNNECESSARY)
    {
        return new Money($this->amount->withScale($scale, $roundingMode), $this->currency);
    }

    /**
     * Rounds a copy of this Money with the default scale of the currency in use.
     *
     * @param int $roudingMode The rounding mode to apply, if necessary.
     *
     * @return Money
     */
    public function withDefaultScale($roudingMode = RoundingMode::UNNECESSARY)
    {
        return $this->withScale($this->currency->getDefaultFractionDigits(), $roudingMode);
    }

    /**
     * Returns the sum of this Money and the given amount.
     *
     * The resulting Money has the same scale as this Money.
     *
     * @param Money|BigNumber|number|string $that         The amount to be added.
     * @param int                           $roundingMode The rounding mode to use, if necessary.
     *
     * @return Money
     */
    public function plus($that, $roundingMode = RoundingMode::UNNECESSARY)
    {
        if ($that instanceof Money) {
            $this->checkCurrency($that->currency);
            $that = $that->amount;
        }

        $amount = $this->amount->plus($that);
        $amount = $amount->toScale($this->amount->scale(), $roundingMode);

        return new Money($amount, $this->currency);
    }

    /**
     * Returns the difference of this Money and the given amount.
     *
     * The resulting Money has the same scale as this Money.
     *
     * @param Money|BigNumber|number|string $that         The amount to be subtracted.
     * @param int                           $roundingMode The rounding mode to use, if necessary.
     *
     * @return Money
     */
    public function minus($that, $roundingMode = RoundingMode::UNNECESSARY)
    {
        if ($that instanceof Money) {
            $this->checkCurrency($that->currency);
            $that = $that->amount;
        }

        $amount = $this->amount->minus($that);
        $amount = $amount->toScale($this->amount->scale(), $roundingMode);

        return new Money($amount, $this->currency);
    }

    /**
     * Returns the product of this Money and the given number.
     *
     * The resulting Money has the same scale as this Money.
     *
     * @param Money|BigNumber|number|string $that         The multiplier.
     * @param int                           $roundingMode The rounding mode to use, if necessary.
     *
     * @return Money
     */
    public function multipliedBy($that, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $amount = $this->amount->multipliedBy($that);
        $amount = $amount->toScale($this->amount->scale(), $roundingMode);

        return new Money($amount, $this->currency);
    }

    /**
     * Returns the result of the division of this Money by the given number.
     *
     * The resulting Money has the same scale as this Money.
     *
     * @param Money|BigNumber|number|string $that         The divisor.
     * @param int                           $roundingMode The rounding mode to use, if necessary.
     *
     * @return Money
     */
    public function dividedBy($that, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $amount = $this->amount->dividedBy($that, $this->amount->scale(), $roundingMode);

        return new Money($amount, $this->currency);
    }

    /**
     * Returns a Money whose value is the absolute value of this Money.
     *
     * @return Money
     */
    public function abs()
    {
        return new Money($this->amount->abs(), $this->currency);
    }

    /**
     * Returns a Money whose value is the negated value of this Money.
     *
     * @return Money
     */
    public function negated()
    {
        return new Money($this->amount->negated(), $this->currency);
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
     * Compares this Money to the given Money.
     *
     * @param Money|BigNumber|number|string $that
     *
     * @return int -1, 0 or 1.
     *
     * @throws CurrencyMismatchException
     */
    public function compareTo($that)
    {
        if ($that instanceof Money) {
            $this->checkCurrency($that->currency);
            $that = $that->amount;
        }

        return $this->amount->compareTo($that);
    }

    /**
     * Returns whether this Money is equal to the given Money.
     *
     * @param Money|BigNumber|number|string $that
     *
     * @return bool
     *
     * @throws CurrencyMismatchException
     */
    public function isEqualTo($that)
    {
        if ($that instanceof Money) {
            $this->checkCurrency($that->currency);
            $that = $that->amount;
        }

        return $this->amount->isEqualTo($that);
    }

    /**
     * Returns whether this Money is less than the given amount.
     *
     * @param Money|BigNumber|number|string $that
     *
     * @return bool
     *
     * @throws CurrencyMismatchException
     */
    public function isLessThan($that)
    {
        if ($that instanceof Money) {
            $this->checkCurrency($that->currency);
            $that = $that->amount;
        }

        return $this->amount->isLessThan($that);
    }

    /**
     * Returns whether this Money is less than or equal to the given amount.
     *
     * @param Money|BigNumber|number|string $that
     *
     * @return bool
     *
     * @throws CurrencyMismatchException
     */
    public function isLessThanOrEqualTo($that)
    {
        if ($that instanceof Money) {
            $this->checkCurrency($that->currency);
            $that = $that->amount;
        }

        return $this->amount->isLessThanOrEqualTo($that);
    }

    /**
     * Returns whether this Money is greater than the given Money.
     *
     * @param Money|BigNumber|number|string $that
     *
     * @return bool
     *
     * @throws CurrencyMismatchException
     */
    public function isGreaterThan($that)
    {
        if ($that instanceof Money) {
            $this->checkCurrency($that->currency);
            $that = $that->amount;
        }

        return $this->amount->isGreaterThan($that);
    }

    /**
     * Returns whether this Money is greater than or equal to the given Money.
     *
     * @param Money|BigNumber|number|string $that
     *
     * @return bool
     *
     * @throws CurrencyMismatchException
     */
    public function isGreaterThanOrEqualTo($that)
    {
        if ($that instanceof Money) {
            $this->checkCurrency($that->currency);
            $that = $that->amount;
        }

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
        return $this->currency->getCode() . ' ' . $this->amount;
    }
}
