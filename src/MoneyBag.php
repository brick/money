<?php

namespace Brick\Money;

use Brick\Money\Adjustment\ExactResult;

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
     * If no money is present for the given currency, a zero-value money with the default scale
     * for the given currency will be returned.
     *
     * @param Currency|string $currency
     *
     * @return Money
     */
    public function get($currency)
    {
        $currency = Currency::of($currency);
        $currencyCode = $currency->getCurrencyCode();

        return isset($this->monies[$currencyCode])
            ? $this->monies[$currencyCode]
            : Money::zero($currency);
    }

    /**
     * {@inheritdoc}
     */
    public function getMonies()
    {
        return array_values($this->monies);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($currency, CurrencyConverter $converter)
    {
        $currency = Currency::of($currency);
        $total = Money::zero($currency);
        $adjustment = new ExactResult();

        foreach ($this->monies as $money) {
            $money = $converter->convert($money, $currency);
            $total = $total->plus($money, $adjustment);
        }

        return $total;
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
            $currencyCode = $currency->getCurrencyCode();

            $currentValue = $this->get($currency);

            if ($money->getAmount()->scale() > $currentValue->getAmount()->scale()) {
                $this->monies[$currencyCode] = $money->plus($this->get($currency));
            } else {
                $this->monies[$currencyCode] = $this->get($currency)->plus($money);
            }
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
            $currencyCode = $currency->getCurrencyCode();

            $currentValue = $this->get($currency);

            if ($money->getAmount()->scale() > $currentValue->getAmount()->scale()) {
                $this->monies[$currencyCode] = $money->negated()->plus($this->get($currency));
            } else {
                $this->monies[$currencyCode] = $this->get($currency)->minus($money);
            }
        }

        return $this;
    }
}
