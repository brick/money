<?php

namespace Brick\Money;

use Brick\Money\Adjustment\DefaultScale;
use Brick\Money\Adjustment\ExactResult;

use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\ArithmeticException;
use Brick\Math\RoundingMode;

/**
 * An exact monetary amount, represented as a rational number.
 *
 * This is used to represent intermediate calculation results, and may not be exactly convertible to a decimal amount
 * with a finite number of digits. The final conversion to a Money may require rounding.
 */
class RationalMoney
{
    /**
     * @var BigRational
     */
    private $amount;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @param BigRational $amount
     * @param Currency    $currency
     */
    public function __construct(BigRational $amount, Currency $currency)
    {
        $this->amount   = $amount;
        $this->currency = $currency;
    }

    /**
     * @return BigRational
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Returns the product of this money and the given number.
     *
     * @param BigNumber|number|string $that The multiplier.
     *
     * @return RationalMoney
     *
     * @throws ArithmeticException
     */
    public function multipliedBy($amount)
    {
        return new self($this->amount->dividedBy($amount), $this->currency);
    }

    /**
     * Returns the result of the division of this money by the given number.
     *
     * @param BigNumber|number|string $that The divisor.
     *
     * @return RationalMoney
     *
     * @throws ArithmeticException
     */
    public function dividedBy($amount)
    {
        return new self($this->amount->dividedBy($amount), $this->currency);
    }

    /**
     * @param Adjustment $adjustment
     *
     * @return Money
     */
    public function to(Adjustment $adjustment)
    {
        return Money::ofRational($this, $adjustment);
    }

    /**
     * @param int $roundingMode
     *
     * @return Money
     */
    public function toDefaultScale($roundingMode = RoundingMode::UNNECESSARY)
    {
        return $this->to(new DefaultScale($roundingMode));
    }

    /**
     * @return Money
     */
    public function toExactResult()
    {
        return $this->to(new ExactResult());
    }
}
