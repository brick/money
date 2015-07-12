<?php

namespace Brick\Money;

/**
 * Container for monies in different currencies.
 *
 * This class is mutable.
 */
class MoneyBag implements MoneyContainer
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
     * Returns the total of the monies contained in this bag, in the given currency.
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
            $total = $total->plusExact($money);
        }

        return $total;
    }

    /**
     * {@inheritdoc}
     */
    public function getMonies()
    {
        return array_values($this->monies);
    }

    /**
     * Adds monies to this bag.
     *
     * @param MoneyContainer $that The `Money` or `MoneyBag` to add.
     *
     * @return MoneyBag This instance.
     */
    public function add(MoneyContainer $that)
    {
        foreach ($that->getMonies() as $money) {
            $currency = $money->getCurrency();
            $currencyCode = $currency->getCode();
            $this->monies[$currencyCode] = $this->get($currency)->plusExact($money);
        }

        return $this;
    }

    /**
     * Subtracts monies from this bag.
     *
     * @param MoneyContainer $that The `Money` or `MoneyBag` to subtract.
     *
     * @return MoneyBag This instance.
     */
    public function subtract(MoneyContainer $that)
    {
        foreach ($that->getMonies() as $money) {
            $currency = $money->getCurrency();
            $currencyCode = $currency->getCode();
            $this->monies[$currencyCode] = $this->get($currency)->minusExact($money);
        }

        return $this;
    }
}
