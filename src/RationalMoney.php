<?php

namespace Brick\Money;

use Brick\Money\Exception\MoneyMismatchException;

use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\ArithmeticException;
use Brick\Math\Exception\RoundingNecessaryException;
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
     * Class constructor.
     *
     * @param BigRational $amount   The amount.
     * @param Currency    $currency The currency.
     */
    public function __construct(BigRational $amount, Currency $currency)
    {
        $this->amount   = $amount;
        $this->currency = $currency;
    }

    /**
     * Convenience factory method.
     *
     * @param BigNumber|number|string $amount   The monetary amount.
     * @param Currency|string         $currency The currency, as a Currency instance or ISO currency code.
     *
     * @return RationalMoney
     */
    public static function of($amount, $currency)
    {
        $amount = BigRational::of($amount);
        $currency = Currency::of($currency);

        return new RationalMoney($amount, $currency);
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
     * Returns the sum of this RationalMoney and the given amount.
     *
     * @param RationalMoney|Money|BigNumber|number|string $that The amount to add.
     *
     * @return RationalMoney
     *
     * @throws ArithmeticException    If the argument is not a valid number.
     * @throws MoneyMismatchException If the argument is a RationalMoney or a Money in another currency.
     */
    public function plus($that)
    {
        $amount = $this->getAmountFrom($that);
        $amount = $this->amount->plus($amount);

        return new self($amount, $this->currency);
    }

    /**
     * Returns the difference of this RationalMoney and the given amount.
     *
     * @param RationalMoney|Money|BigNumber|number|string $that The amount to subtract.
     *
     * @return RationalMoney
     *
     * @throws ArithmeticException    If the argument is not a valid number.
     * @throws MoneyMismatchException If the argument is a RationalMoney or a Money in another currency.
     */
    public function minus($that)
    {
        $amount = $this->getAmountFrom($that);
        $amount = $this->amount->minus($amount);

        return new self($amount, $this->currency);
    }

    /**
     * Returns the product of this RationalMoney and the given number.
     *
     * @param BigNumber|number|string $that The multiplier.
     *
     * @return RationalMoney
     *
     * @throws ArithmeticException If the argument is not a valid number.
     */
    public function multipliedBy($that)
    {
        $amount = $this->amount->multipliedBy($that);

        return new self($amount, $this->currency);
    }

    /**
     * Returns the result of the division of this RationalMoney by the given number.
     *
     * @param BigNumber|number|string $that The divisor.
     *
     * @return RationalMoney
     *
     * @throws ArithmeticException If the argument is not a valid number.
     */
    public function dividedBy($that)
    {
        $amount = $this->amount->dividedBy($that);

        return new self($amount, $this->currency);
    }

    /**
     * @param Context $context
     * @param int     $roundingMode
     *
     * @return Money
     */
    public function to(Context $context, $roundingMode = RoundingMode::UNNECESSARY)
    {
        return Money::ofRational($this, $context, $roundingMode);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            $amount = $this->amount->toBigDecimal();
        } catch (RoundingNecessaryException $e) {
            $amount = $this->amount->simplified();
        }

        return $this->currency . ' ' . $amount;
    }

    /**
     * Returns the amount of the given parameter.
     *
     * If the parameter is a RationalMoney or a Money, its Currency is checked against this object's Currency.
     *
     * @param RationalMoney|Money|BigNumber|number|string $that
     *
     * @return BigNumber|number|string
     *
     * @throws MoneyMismatchException If the given currency is not equal to this RationalMoney's currency.
     */
    private function getAmountFrom($that)
    {
        if ($that instanceof RationalMoney) {
            $this->checkCurrency($that->currency);

            return $that->amount;
        }

        if ($that instanceof Money) {
            $this->checkCurrency($that->getCurrency());

            return $that->getAmount();
        }

        return $that;
    }

    /**
     * @param Currency $currency
     *
     * @return void
     *
     * @throws MoneyMismatchException If the given currency is not equal to this RationalMoney's currency.
     */
    private function checkCurrency(Currency $currency)
    {
        if (! $currency->is($this->currency)) {
            throw MoneyMismatchException::currencyMismatch($this->currency, $currency);
        }
    }
}
