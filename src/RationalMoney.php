<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\MathException;
use Brick\Money\Exception\MoneyMismatchException;
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
     * @throws MathException            If the amount is not a valid number.
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
     * @throws MathException          If the argument is not a valid number.
     * @throws MoneyMismatchException If the argument is a money in another currency.
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
     * @throws MathException          If the argument is not a valid number.
     * @throws MoneyMismatchException If the argument is a money in another currency.
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
     * @throws MathException If the argument is not a valid number.
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
     * @throws MathException If the argument is not a valid number.
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
     */
    #[Override]
    public function abs(): static
    {
        return new self($this->amount->abs(), $this->currency);
    }

    /**
     * Returns a RationalMoney whose value is the negated value of this RationalMoney.
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
     * @throws MathException            If the exchange rate is not a valid number.
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
