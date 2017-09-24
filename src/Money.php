<?php

namespace Brick\Money;

use Brick\Money\Context\PrecisionContext;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Context\ExactContext;
use Brick\Money\Exception\CurrencyMismatchException;
use Brick\Money\Exception\MoneyParseException;
use Brick\Money\Exception\UnknownCurrencyException;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\ArithmeticException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;

/**
 * A monetary value in a given currency. This class is immutable.
 *
 * A Money has an amount, a currency, and a fixed capability: scale and step. The step is useful for cash roundings.
 * For example, CHF has no coins below 5 cents, so a cash CHF money can be represented with step 5.
 *
 * A Money with scale 2 and step 1 can contain amounts of -0.01, 0.00, 0.01, etc.
 * A Money with scale 2 and step 5 can contain amounts of -0.05, 0.00, 0.05, etc.
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
     * The context that defines the capability of this Money.
     *
     * @var Context
     */
    private $context;

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
     * To create a Money with a custom capability (scale and step), a Context instance can be provided.
     *
     * @param BigNumber|number|string $amount       The monetary amount.
     * @param Currency|string         $currency     The currency, as a `Currency` object or currency code string.
     * @param Context|null            $context      An optional Context.
     * @param int                     $roundingMode An optional rounding mode.
     *
     * @return Money
     *
     * @throws NumberFormatException      If the amount is a string in a non-supported format.
     * @throws RoundingNecessaryException If the rounding was necessary to represent the amount at the requested scale.
     */
    public static function of($amount, $currency, Context $context = null, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $currency = Currency::of($currency);

        if ($context === null) {
            $context = new DefaultContext();
        }

        $amount = BigNumber::of($amount);

        return self::applyContext($amount, $currency, $context, $roundingMode);
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

        return new Money($amount, $currency, new DefaultContext());
    }

    /**
     * @param RationalMoney $money
     * @param Context       $context
     * @param int           $roundingMode
     *
     * @return Money
     */
    public static function ofRational(RationalMoney $money, Context $context, $roundingMode = RoundingMode::UNNECESSARY)
    {
        return self::applyContext($money->getAmount(), $money->getCurrency(), $context, $roundingMode);
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

        return new Money($amount, $currency, new PrecisionContext($amount->scale()));
    }

    /**
     * Returns a Money with zero value, in the given Currency.
     *
     * By default, the resulting Money has the default scale for the currency.
     * This behaviour can be overridden by providing a Context instance.
     *
     * @param Currency|string $currency A currency instance or currency code.
     * @param Context|null    $context  An optional context.
     *
     * @return Money
     */
    public static function zero($currency, Context $context = null)
    {
        $currency = Currency::of($currency);

        if ($context === null) {
            $context = new DefaultContext();
        }

        $amount = BigDecimal::zero();

        return self::applyContext($amount, $currency, $context, RoundingMode::UNNECESSARY);
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
     * Applies the given context to this Money, and returns the result.
     *
     * @param Context $context
     * @param int     $roundingMode
     *
     * @return Money
     */
    public function with(Context $context, $roundingMode = RoundingMode::UNNECESSARY)
    {
        return self::applyContext($this->amount, $this->currency, $context, $roundingMode);
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
        $context = new PrecisionContext($fractionDigits);

        return new Money($this->amount->toScale($fractionDigits, $roundingMode), $this->currency, $context);
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
     * The resulting Money has the same capability (scale and step) as this Money. If the result needs rounding to fit
     * this capability, a rounding mode can be provided. If a rounding mode is not provided and rounding is necessary,
     * an exception is thrown.
     *
     * @param Money|BigNumber|number|string $that         The amount to add.
     * @param int                           $roundingMode An optional RoundingMode constant.
     *
     * @return Money
     *
     * @throws ArithmeticException       If the argument is an invalid number or rounding is necessary.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     */
    public function plus($that, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $that = $this->handleMoney($that);
        $amount = $this->amount->plus($that);

        return self::applyContext($amount, $this->currency, $this->context, $roundingMode);
    }

    /**
     * Returns the difference of this Money and the given amount.
     *
     * The resulting Money has the same capability (scale and step) as this Money. If the result needs rounding to fit
     * this capability, a rounding mode can be provided. If a rounding mode is not provided and rounding is necessary,
     * an exception is thrown.
     *
     * @param Money|BigNumber|number|string $that         The amount to subtract.
     * @param int                           $roundingMode An optional RoundingMode constant.
     *
     * @return Money
     *
     * @throws ArithmeticException       If the argument is an invalid number or rounding is necessary.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     */
    public function minus($that, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $that = $this->handleMoney($that);
        $amount = $this->amount->minus($that);

        return self::applyContext($amount, $this->currency, $this->context, $roundingMode);
    }

    /**
     * Returns the product of this Money and the given number.
     *
     * The resulting Money has the same capability (scale and step) as this Money. If the result needs rounding to fit
     * this capability, a rounding mode can be provided. If a rounding mode is not provided and rounding is necessary,
     * an exception is thrown.
     *
     * @param BigNumber|number|string $that         The multiplier.
     * @param int                     $roundingMode An optional RoundingMode constant.
     *
     * @return Money
     *
     * @throws ArithmeticException If the argument is an invalid number or rounding is necessary.
     */
    public function multipliedBy($that, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $amount = $this->amount->multipliedBy($that);

        return self::applyContext($amount, $this->currency, $this->context, $roundingMode);
    }

    /**
     * Returns the result of the division of this Money by the given number.
     *
     * The resulting Money has the same capability (scale and step) as this Money. If the result needs rounding to fit
     * this capability, a rounding mode can be provided. If a rounding mode is not provided and rounding is necessary,
     * an exception is thrown.
     *
     * @param BigNumber|number|string $that         The divisor.
     * @param int                     $roundingMode An optional RoundingMode constant.
     *
     * @return Money
     *
     * @throws ArithmeticException If the argument is an invalid number or is zero, or rounding is necessary.
     */
    public function dividedBy($that, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $amount = $this->amount->toBigRational()->dividedBy($that);

        return self::applyContext($amount, $this->currency, $this->context, $roundingMode);
    }

    /**
     * Returns the quotient and the remainder of the division of this Money by the given number.
     *
     * The resulting Money has the same capability (scale and step) as this Money.
     *
     * The given number must be an integer value.
     *
     * This method can serve as a basis for a money allocation algorithm.
     *
     * @param BigNumber|number|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @return Money
     *
     * @throws ArithmeticException If the divisor cannot be converted to a BigInteger.
     */
    public function quotient($that)
    {
        $that = BigInteger::of($that);
        $step = $this->context->getStep();

        $scale  = $this->amount->scale();
        $amount = $this->amount->withPointMovedRight($scale)->dividedBy($step);

        $q = $amount->quotient($that);
        $q = $q->multipliedBy($step)->withPointMovedLeft($scale);

        return new Money($q, $this->currency, $this->context);
    }

    /**
     * Returns the quotient and the remainder of the division of this Money by the given number.
     *
     * The resulting Money has the same capability (scale and step) as this Money.
     *
     * The given number must be an integer value. Note that support for decimal divisors is not provided,
     * as this would yield a remainder whose scale might be incompatible with this Money.
     *
     * This method can serve as a basis for a money allocation algorithm.
     *
     * @param BigNumber|number|string $that The divisor. Must be convertible to a BigInteger.
     *
     * @return Money[] The quotient and the remainder.
     *
     * @throws ArithmeticException If the divisor cannot be converted to a BigInteger.
     */
    public function quotientAndRemainder($that)
    {
        $that = BigInteger::of($that);
        $step = $this->context->getStep();

        $scale  = $this->amount->scale();
        $amount = $this->amount->withPointMovedRight($scale)->dividedBy($step);

        list ($q, $r) = $amount->quotientAndRemainder($that);

        $q = $q->multipliedBy($step)->withPointMovedLeft($scale);
        $r = $r->multipliedBy($step)->withPointMovedLeft($scale);

        $quotient  = new Money($q, $this->currency, $this->context);
        $remainder = new Money($r, $this->currency, $this->context);

        return [$quotient, $remainder];
    }

    /**
     * Allocates this Money according to a list of ratios.
     *
     * The resulting monies have the same capability (scale and step) as this Money.
     *
     * @param int[] $ratios
     *
     * @return Money[]
     */
    public function allocate(array $ratios)
    {
        $total = array_sum($ratios);
        $step = $this->context->getStep();

        $monies = [];

        $unit = BigDecimal::ofUnscaledValue($step, $this->amount->scale());
        $unit = new Money($unit, $this->currency, $this->context);

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
     * Returns a Money whose value is the absolute value of this Money.
     *
     * @return Money
     */
    public function abs()
    {
        return new Money($this->amount->abs(), $this->currency, $this->context);
    }

    /**
     * Returns a Money whose value is the negated value of this Money.
     *
     * @return Money
     */
    public function negated()
    {
        return new Money($this->amount->negated(), $this->currency, $this->context);
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
     * The scale can be adjusted by providing a Context instance.
     *
     * @param Currency|string         $currency     The target currency or currency code.
     * @param BigNumber|number|string $exchangeRate The exchange rate to multiply by.
     * @param Context|null            $context      An optional context.
     * @param int                     $roundingMode An optional rounding mode.
     *
     * @return Money
     *
     * @throws UnknownCurrencyException If an unknown currency code is given.
     * @throws ArithmeticException      If the exchange rate or rounding mode is invalid, or rounding is necessary.
     */
    public function convertedTo($currency, $exchangeRate, Context $context = null, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $currency = Currency::of($currency);

        if ($context === null) {
            $context = new ExactContext();
        }

        $amount = $this->amount->toBigRational()->multipliedBy($exchangeRate);

        return self::applyContext($amount, $currency, $context, $roundingMode);
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
     * @return RationalMoney
     */
    public function toRational()
    {
        return new RationalMoney($this->amount->toBigRational(), $this->currency);
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
     * @param BigNumber $amount
     * @param Currency  $currency
     * @param Context   $context
     * @param int       $roundingMode
     *
     * @return Money
     */
    private static function applyContext(BigNumber $amount, Currency $currency, Context $context, $roundingMode)
    {
        $amount = $context->applyTo($amount, $currency, $roundingMode);

        return new Money($amount, $currency, $context);
    }
}
