<?php

declare(strict_types=1);

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
     * @psalm-var array<string, BigRational>
     *
     * @var BigRational[]
     */
    private $amounts = [];

    /**
     * Returns the amount in the given currency contained in the bag, as a rational number.
     *
     * Non-ISO (non-numeric) currency codes are accepted.
     *
     * @param Currency|string|int $currency The Currency instance, currency code or ISO numeric currency code.
     *
     * @return BigRational
     */
    public function getAmount($currency) : BigRational
    {
        if (is_int($currency)) {
            $currencyCode = (string) Currency::of($currency);
        } else {
            $currencyCode = (string) $currency;
        }

        return isset($this->amounts[$currencyCode])
            ? $this->amounts[$currencyCode]
            : BigRational::zero();
    }

    /**
     * Returns the amounts contained in this bag, as rational numbers, indexed by currency code.
     *
     * @psalm-return array<string, BigRational>
     *
     * @return BigRational[]
     */
    public function getAmounts() : array
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
    public function add(MoneyContainer $money) : MoneyBag
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
    public function subtract(MoneyContainer $money) : MoneyBag
    {
        foreach ($money->getAmounts() as $currencyCode => $amount) {
            $this->amounts[$currencyCode] = $this->getAmount($currencyCode)->minus($amount);
        }

        return $this;
    }
}
