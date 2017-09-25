<?php

namespace Brick\Money;

use Brick\Money\Context\ExactContext;

use Brick\Math\BigDecimal;

/**
 * Container for monies in different currencies.
 *
 * This class is mutable.
 */
class MoneyBag
{
    /**
     * The amounts in this bag, indexed by currency code.
     *
     * @var BigDecimal[]
     */
    private $amounts = [];

    /**
     * Returns the amount in the given currency contained in the bag.
     *
     * @param Currency|string $currency
     *
     * @return BigDecimal
     */
    public function get($currency)
    {
        $currencyCode = (string) $currency;

        return isset($this->amounts[$currencyCode])
            ? $this->amounts[$currencyCode]
            : BigDecimal::zero();
    }

    /**
     * Returns the amounts contained in this bag, indexed by currency code.
     *
     * @return BigDecimal[]
     */
    public function getAmounts()
    {
        return $this->amounts;
    }

    /**
     * Adds a Money to this bag.
     *
     * @param Money $money
     *
     * @return MoneyBag This instance.
     */
    public function add(Money $money)
    {
        $currency = $money->getCurrency();
        $currencyCode = $currency->getCurrencyCode();

        $this->amounts[$currencyCode] = $this->get($currency)->plus($money->getAmount());

        return $this;
    }

    /**
     * Subtracts a Money from this bag.
     *
     * @param Money $money
     *
     * @return MoneyBag This instance.
     */
    public function subtract(Money $money)
    {
        $currency = $money->getCurrency();
        $currencyCode = $currency->getCurrencyCode();

        $this->amounts[$currencyCode] = $this->get($currency)->minus($money->getAmount());

        return $this;
    }
}
