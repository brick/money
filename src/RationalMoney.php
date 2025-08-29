<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\MathException;
use Brick\Money\Exception\MoneyMismatchException;

/**
 * An exact monetary amount, represented as a rational number. This class is immutable.
 *
 * This is used to represent intermediate calculation results, and may not be exactly convertible to a decimal amount
 * with a finite number of digits. The final conversion to a Money may require rounding.
 */
final class RationalMoney extends AbstractMoney
{
    private readonly BigRational $amount;

    private readonly Currency $currency;

    /**
     * Class constructor.
     *
     * @param BigRational $amount   The amount.
     * @param Currency    $currency The currency.
     */
    public function __construct(BigRational $amount, Currency $currency)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    /**
     * Convenience factory method.
     *
     * @param BigNumber|int|float|string $amount   The monetary amount.
     * @param Currency|string|int        $currency The Currency instance, ISO currency code or ISO numeric currency code.
     */
    public static function of(BigNumber|int|float|string $amount, Currency|string|int $currency): RationalMoney
    {
        $amount = BigRational::of($amount);

        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        return new RationalMoney($amount, $currency);
    }

    public function getAmount(): BigRational
    {
        return $this->amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * Returns the sum of this RationalMoney and the given amount.
     *
     * @param AbstractMoney|BigNumber|int|float|string $that The money or amount to add.
     *
     * @throws MathException          If the argument is not a valid number.
     * @throws MoneyMismatchException If the argument is a money in another currency.
     */
    public function plus(AbstractMoney|BigNumber|int|float|string $that): RationalMoney
    {
        $that = $this->getAmountOf($that);
        $amount = $this->amount->plus($that);

        return new self($amount, $this->currency);
    }

    /**
     * Returns the difference of this RationalMoney and the given amount.
     *
     * @param AbstractMoney|BigNumber|int|float|string $that The money or amount to subtract.
     *
     * @throws MathException          If the argument is not a valid number.
     * @throws MoneyMismatchException If the argument is a money in another currency.
     */
    public function minus(AbstractMoney|BigNumber|int|float|string $that): RationalMoney
    {
        $that = $this->getAmountOf($that);
        $amount = $this->amount->minus($that);

        return new self($amount, $this->currency);
    }

    /**
     * Returns the product of this RationalMoney and the given number.
     *
     * @param BigNumber|int|float|string $that The multiplier.
     *
     * @throws MathException If the argument is not a valid number.
     */
    public function multipliedBy(BigNumber|int|float|string $that): RationalMoney
    {
        $amount = $this->amount->multipliedBy($that);

        return new self($amount, $this->currency);
    }

    /**
     * Returns the result of the division of this RationalMoney by the given number.
     *
     * @param BigNumber|int|float|string $that The divisor.
     *
     * @throws MathException If the argument is not a valid number.
     */
    public function dividedBy(BigNumber|int|float|string $that): RationalMoney
    {
        $amount = $this->amount->dividedBy($that);

        return new self($amount, $this->currency);
    }

    /**
     * Returns a copy of this BigRational, with the amount simplified.
     */
    public function simplified(): RationalMoney
    {
        return new self($this->amount->simplified(), $this->currency);
    }

    public function __toString(): string
    {
        return $this->currency . ' ' . $this->amount;
    }
}
