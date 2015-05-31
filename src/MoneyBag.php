<?php

namespace Brick\Money;

use Brick\Money\Currency;

/**
 * Contains monies in different currencies. This class is mutable.
 */
class MoneyBag
{
    /**
     * The monies in this bag, indexed by currency code.
     *
     * @var Money[]
     */
    private $monies = [];

    /**
     * Returns the money in the given currency contained in the bag.
     *
     * If no money is present for the given currency, a zero-value money will be returned.
     *
     * @param Currency $currency
     *
     * @return Money
     */
    public function get(Currency $currency)
    {
        $currencyCode = $currency->getCode();

        return isset($this->monies[$currencyCode])
            ? $this->monies[$currencyCode]
            : Money::zero($currency);
    }

    /**
     * Returns the total money contained in the bag, in the given currency.
     *
     * @param Currency          $currency  The currency to get the total in.
     * @param CurrencyConverter $converter The currency converter to use.
     *
     * @return Money The total in the given currency.
     */
    public function getTotal(Currency $currency, CurrencyConverter $converter)
    {
        $total = Money::zero($currency);

        foreach ($this->monies as $money) {
            $money = $converter->convert($money, $currency);
            $total = $total->plus($money);
        }

        return $total;
    }

    /**
     * Returns all the monies inside this bag.
     *
     * @return Money[]
     */
    public function getMonies()
    {
        return $this->monies;
    }

    /**
     * Adds the given money to this bag.
     *
     * @param Money $money
     *
     * @return MoneyBag This instance.
     */
    public function add(Money $money)
    {
        $currency = $money->getCurrency();
        $currencyCode = $currency->getCode();
        $this->monies[$currencyCode] = $money->plus($this->get($currency));

        return $this;
    }

    /**
     * Subtracts the given money from this bag.
     *
     * @param Money $money
     *
     * @return MoneyBag This instance.
     */
    public function subtract(Money $money)
    {
        return $this->add($money->negated());
    }

    /**
     * @param MoneyBag $moneyBag
     *
     * @return MoneyBag This instance.
     */
    public function addMoneyBag(MoneyBag $moneyBag)
    {
        foreach ($moneyBag->getMonies() as $money) {
            $this->add($money);
        }

        return $this;
    }
}
