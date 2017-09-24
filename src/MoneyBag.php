<?php

namespace Brick\Money;

use Brick\Money\Context\ExactContext;

/**
 * Container for monies in different currencies.
 *
 * This class is mutable.
 *
 * @todo use BigDecimal internally.
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
     * @return Money[]
     */
    public function getMonies()
    {
        return array_values($this->monies);
    }

    /**
     * @param Currency|string   $currency
     * @param CurrencyConverter $converter
     *
     * @return Money
     */
    public function getValue($currency, CurrencyConverter $converter)
    {
        $currency = Currency::of($currency);
        $total = Money::zero($currency);

        foreach ($this->monies as $money) {
            $money = $converter->convert($money, $currency);
            $total = $total->toRational()->plus($money->getAmount())->toExactResult();
        }

        return $total;
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

        $currentValue = $this->get($currency);

        if ($money->getAmount()->scale() > $currentValue->getAmount()->scale()) {
            $this->monies[$currencyCode] = $money->plus($this->get($currency));
        } else {
            $this->monies[$currencyCode] = $this->get($currency)->plus($money);
        }

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

        $currentValue = $this->get($currency);

        if ($money->getAmount()->scale() > $currentValue->getAmount()->scale()) {
            $this->monies[$currencyCode] = $money->negated()->plus($this->get($currency));
        } else {
            $this->monies[$currencyCode] = $this->get($currency)->minus($money);
        }

        return $this;
    }
}
