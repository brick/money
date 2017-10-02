<?php

namespace Brick\Money;

use Brick\Money\Exception\MoneyMismatchException;

use Brick\Math\BigNumber;
use Brick\Math\Exception\ArithmeticException;

/**
 * Base class for Money and RationalMoney.
 */
abstract class AbstractMoney implements MoneyContainer
{
    /**
     * @return BigNumber
     */
    abstract public function getAmount();

    /**
     * @return Currency
     */
    abstract public function getCurrency();

    /**
     * Required by interface MoneyContainer.
     *
     * @return BigNumber[]
     */
    final public function getAmounts()
    {
        return [
            $this->getCurrency()->getCurrencyCode() => $this->getAmount()
        ];
    }

    /**
     * Returns the sign of this money.
     *
     * @return int -1 if the number is negative, 0 if zero, 1 if positive.
     */
    final public function getSign()
    {
        return $this->getAmount()->sign();
    }

    /**
     * Returns whether this money has zero value.
     *
     * @return bool
     */
    final public function isZero()
    {
        return $this->getAmount()->isZero();
    }

    /**
     * Returns whether this money has a negative value.
     *
     * @return bool
     */
    final public function isNegative()
    {
        return $this->getAmount()->isNegative();
    }

    /**
     * Returns whether this money has a negative or zero value.
     *
     * @return bool
     */
    final public function isNegativeOrZero()
    {
        return $this->getAmount()->isNegativeOrZero();
    }

    /**
     * Returns whether this money has a positive value.
     *
     * @return bool
     */
    final public function isPositive()
    {
        return $this->getAmount()->isPositive();
    }

    /**
     * Returns whether this money has a positive or zero value.
     *
     * @return bool
     */
    final public function isPositiveOrZero()
    {
        return $this->getAmount()->isPositiveOrZero();
    }

    /**
     * Compares this money to the given amount.
     *
     * @param AbstractMoney|BigNumber|number|string $that
     *
     * @return int [-1, 0, 1] if `$this` is less than, equal to, or greater than `$that`.
     *
     * @throws ArithmeticException    If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    final public function compareTo($that)
    {
        return $this->getAmount()->compareTo($this->getAmountOf($that));
    }

    /**
     * Returns whether this money is equal to the given amount.
     *
     * @param AbstractMoney|BigNumber|number|string $that
     *
     * @return bool
     *
     * @throws ArithmeticException    If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    final public function isEqualTo($that)
    {
        return $this->getAmount()->isEqualTo($this->getAmountOf($that));
    }

    /**
     * Returns whether this money is less than the given amount.
     *
     * @param AbstractMoney|BigNumber|number|string $that
     *
     * @return bool
     *
     * @throws ArithmeticException    If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    final public function isLessThan($that)
    {
        return $this->getAmount()->isLessThan($this->getAmountOf($that));
    }

    /**
     * Returns whether this money is less than or equal to the given amount.
     *
     * @param AbstractMoney|BigNumber|number|string $that
     *
     * @return bool
     *
     * @throws ArithmeticException    If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    final public function isLessThanOrEqualTo($that)
    {
        return $this->getAmount()->isLessThanOrEqualTo($this->getAmountOf($that));
    }

    /**
     * Returns whether this money is greater than the given amount.
     *
     * @param AbstractMoney|BigNumber|number|string $that
     *
     * @return bool
     *
     * @throws ArithmeticException    If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    final public function isGreaterThan($that)
    {
        return $this->getAmount()->isGreaterThan($this->getAmountOf($that));
    }

    /**
     * Returns whether this money is greater than or equal to the given amount.
     *
     * @param AbstractMoney|BigNumber|number|string $that
     *
     * @return bool
     *
     * @throws ArithmeticException    If the argument is an invalid number.
     * @throws MoneyMismatchException If the argument is a money in a different currency.
     */
    final public function isGreaterThanOrEqualTo($that)
    {
        return $this->getAmount()->isGreaterThanOrEqualTo($this->getAmountOf($that));
    }

    /**
     * Returns the amount of the given parameter.
     *
     * If the parameter is a money, its currency is checked against this money's currency.
     *
     * @param AbstractMoney|BigNumber|number|string $that A money or amount.
     *
     * @return BigNumber|number|string
     *
     * @throws MoneyMismatchException If currencies don't match.
     */
    final protected function getAmountOf($that)
    {
        if ($that instanceof AbstractMoney) {
            if (! $that->getCurrency()->is($this->getCurrency())) {
                throw MoneyMismatchException::currencyMismatch($this->getCurrency(), $that->getCurrency());
            }

            return $that->getAmount();
        }

        return $that;
    }
}
