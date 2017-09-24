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
        $currency = Currency::of($currency);
        $currencyCode = $currency->getCurrencyCode();

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
        return array_values($this->amounts);
    }

    /**
     * Returns the total of the monies in this bag, in the given currency.
     *
     * @param Currency|string   $currency
     * @param CurrencyConverter $converter
     *
     * @return Money
     */
    public function getValue($currency, CurrencyConverter $converter)
    {
        $currency = Currency::of($currency);
        $total = Money::zero($currency);

        $context = new ExactContext();

        foreach ($this->amounts as $currencyCode => $amount) {
            $money = Money::of($amount, $currencyCode, $context);
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
