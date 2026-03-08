<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\MathException;
use Brick\Money\Exception\CurrencyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Override;

/**
 * An exact monetary amount, represented as a rational number. This class is immutable.
 *
 * This is used to represent intermediate calculation results, and may not be exactly convertible to a decimal amount
 * with a finite number of digits. The final conversion to a Money may require rounding.
 */
final readonly class RationalMoney extends AbstractMoney
{
    /**
     * @param BigRational $amount   The amount.
     * @param Currency    $currency The currency.
     *
     * @pure
     */
    public function __construct(
        private BigRational $amount,
        private Currency $currency,
    ) {
    }

    /**
     * Convenience factory method.
     *
     * @param BigNumber|int|string $amount   The monetary amount.
     * @param Currency|string      $currency The Currency instance or ISO currency code.
     *
     * @throws MathException            If the amount is an invalid number.
     * @throws UnknownCurrencyException If an unknown currency code is given.
     *
     * @pure
     */
    public static function of(BigNumber|int|string $amount, Currency|string $currency): RationalMoney
    {
        $amount = BigRational::of($amount);

        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        return new RationalMoney($amount, $currency);
    }

    /**
     * Returns a RationalMoney with zero value, in the given currency.
     *
     * @param Currency|string $currency The Currency instance or ISO currency code.
     *
     * @throws UnknownCurrencyException If an unknown currency code is given.
     *
     * @pure
     */
    public static function zero(Currency|string $currency): RationalMoney
    {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        return new RationalMoney(BigRational::zero(), $currency);
    }

    /**
     * Returns the minimum of the given monies.
     *
     * If several monies are equal to the minimum value, the first one is returned.
     *
     * @param RationalMoney $money     The first money.
     * @param RationalMoney ...$monies The subsequent monies.
     *
     * @throws CurrencyMismatchException If the monies do not share the same currency.
     *
     * @pure
     */
    public static function min(RationalMoney $money, RationalMoney ...$monies): RationalMoney
    {
        $min = $money;

        foreach ($monies as $money) {
            if ($min->isGreaterThan($money)) {
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
     * @param RationalMoney $money     The first money.
     * @param RationalMoney ...$monies The subsequent monies.
     *
     * @throws CurrencyMismatchException If the monies do not share the same currency.
     *
     * @pure
     */
    public static function max(RationalMoney $money, RationalMoney ...$monies): RationalMoney
    {
        $max = $money;

        foreach ($monies as $money) {
            if ($max->isLessThan($money)) {
                $max = $money;
            }
        }

        return $max;
    }

    /**
     * Returns the sum of the given monies.
     *
     * @param RationalMoney $money     The first money.
     * @param RationalMoney ...$monies The subsequent monies.
     *
     * @throws CurrencyMismatchException If the monies do not share the same currency.
     *
     * @pure
     */
    public static function sum(RationalMoney $money, RationalMoney ...$monies): RationalMoney
    {
        $sum = $money;

        foreach ($monies as $money) {
            $sum = $sum->plus($money);
        }

        return $sum;
    }

    #[Override]
    public function getAmount(): BigRational
    {
        return $this->amount;
    }

    #[Override]
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * Returns the sum of this RationalMoney and the given amount.
     *
     * @param AbstractMoney|BigNumber|int|string $that The money or amount to add.
     *
     * @throws MathException             If the argument is an invalid number.
     * @throws CurrencyMismatchException If the argument is a money in another currency.
     *
     * @pure
     */
    public function plus(AbstractMoney|BigNumber|int|string $that): RationalMoney
    {
        $that = $this->getAmountOf($that);
        $amount = $this->amount->plus($that);

        return new self($amount, $this->currency);
    }

    /**
     * Returns the difference of this RationalMoney and the given amount.
     *
     * @param AbstractMoney|BigNumber|int|string $that The money or amount to subtract.
     *
     * @throws MathException             If the argument is an invalid number.
     * @throws CurrencyMismatchException If the argument is a money in another currency.
     *
     * @pure
     */
    public function minus(AbstractMoney|BigNumber|int|string $that): RationalMoney
    {
        $that = $this->getAmountOf($that);
        $amount = $this->amount->minus($that);

        return new self($amount, $this->currency);
    }

    /**
     * Returns the product of this RationalMoney and the given number.
     *
     * @param BigNumber|int|string $that The multiplier.
     *
     * @throws MathException If the argument is an invalid number.
     *
     * @pure
     */
    public function multipliedBy(BigNumber|int|string $that): RationalMoney
    {
        $amount = $this->amount->multipliedBy($that);

        return new self($amount, $this->currency);
    }

    /**
     * Returns the result of the division of this RationalMoney by the given number.
     *
     * @param BigNumber|int|string $that The divisor.
     *
     * @throws MathException           If the argument is an invalid number.
     * @throws DivisionByZeroException If the argument is zero.
     *
     * @pure
     */
    public function dividedBy(BigNumber|int|string $that): RationalMoney
    {
        $amount = $this->amount->dividedBy($that);

        return new self($amount, $this->currency);
    }

    /**
     * Returns a RationalMoney whose value is the absolute value of this RationalMoney.
     *
     * @pure
     */
    #[Override]
    public function abs(): static
    {
        return new self($this->amount->abs(), $this->currency);
    }

    /**
     * Returns a RationalMoney whose value is the negated value of this RationalMoney.
     *
     * @pure
     */
    #[Override]
    public function negated(): static
    {
        return new self($this->amount->negated(), $this->currency);
    }

    /**
     * Converts this RationalMoney to another currency, using an exchange rate.
     *
     * @param Currency|string      $currency     The Currency instance or ISO currency code.
     * @param BigNumber|int|string $exchangeRate The exchange rate to multiply by.
     *
     * @throws UnknownCurrencyException If an unknown currency code is given.
     * @throws MathException            If the exchange rate is an invalid number.
     *
     * @pure
     */
    public function convertedTo(Currency|string $currency, BigNumber|int|string $exchangeRate): RationalMoney
    {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        return new self($this->amount->multipliedBy($exchangeRate), $currency);
    }

    #[Override]
    public function toRational(): RationalMoney
    {
        return $this;
    }

    /**
     * @pure
     */
    #[Override]
    public function __toString(): string
    {
        return $this->currency . ' ' . $this->amount;
    }
}
