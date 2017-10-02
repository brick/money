<?php

namespace Brick\Money;

use Brick\Math\BigRational;

/**
 * Container for monies in different currencies.
 *
 * This class is mutable.
 */
final class MoneyBag implements MoneyContainer
{
    /**
     * The amounts in this bag, indexed by currency code.
     *
     * @var BigRational[]
     */
    private $amounts = [];

    /**
     * Returns the amount in the given currency contained in the bag.
     *
     * @param Currency|string $currency The Currency instance or currency code.
     *
     * @return BigRational
     */
    public function getAmount($currency)
    {
        $currencyCode = (string) $currency;

        return isset($this->amounts[$currencyCode])
            ? $this->amounts[$currencyCode]
            : BigRational::zero();
    }

    /**
     * Returns the amounts contained in this bag, indexed by currency code.
     *
     * @return BigRational[]
     */
    public function getAmounts()
    {
        return $this->amounts;
    }

    /**
     * Adds money to this bag.
     *
     * @param MoneyContainer $money A Money, RationalMoney, or MoneyBag instance.
     *
     * @return MoneyBag This instance.
     */
    public function add(MoneyContainer $money)
    {
        foreach ($money->getAmounts() as $currencyCode => $amount) {
            $this->amounts[$currencyCode] = $this->getAmount($currencyCode)->plus($amount);
        }

        return $this;
    }

    /**
     * Subtracts money from this bag.
     *
     * @param MoneyContainer $money A Money, RationalMoney, or MoneyBag instance.
     *
     * @return MoneyBag This instance.
     */
    public function subtract(MoneyContainer $money)
    {
        foreach ($money->getAmounts() as $currencyCode => $amount) {
            $this->amounts[$currencyCode] = $this->getAmount($currencyCode)->minus($amount);
        }

        return $this;
    }
}
