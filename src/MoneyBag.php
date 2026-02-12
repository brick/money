<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigRational;
use Override;

use function array_values;

/**
 * Container for monies in different currencies.
 *
 * This class is mutable.
 */
final class MoneyBag implements Monetary
{
    /**
     * The monies in this bag, indexed by currency code.
     *
     * @var array<string, RationalMoney>
     */
    private array $monies = [];

    /**
     * Returns the contained amount in the given currency as a RationalMoney.
     *
     * @param Currency|string $currency The Currency instance, or ISO currency code.
     */
    public function getMoney(Currency|string $currency): RationalMoney
    {
        $currencyCode = $currency instanceof Currency ? $currency->getCurrencyCode() : $currency;
        $currency = $currency instanceof Currency ? $currency : Currency::of($currency);

        if (isset($this->monies[$currencyCode])) {
            return $this->monies[$currencyCode];
        }

        return new RationalMoney(BigRational::zero(), $currency);
    }

    #[Override]
    public function getMonies(): array
    {
        return array_values($this->monies);
    }

    /**
     * Adds money to this bag.
     *
     * @param Monetary $money A Money, RationalMoney, or MoneyBag instance.
     *
     * @return MoneyBag This instance.
     */
    public function add(Monetary $money): MoneyBag
    {
        foreach ($money->getMonies() as $containedMoney) {
            $currency = $containedMoney->getCurrency();
            $currencyCode = $currency->getCurrencyCode();

            $this->monies[$currencyCode] = $this->getMoney($currency)->plus($containedMoney);
        }

        return $this;
    }

    /**
     * Subtracts money from this bag.
     *
     * @param Monetary $money A Money, RationalMoney, or MoneyBag instance.
     *
     * @return MoneyBag This instance.
     */
    public function subtract(Monetary $money): MoneyBag
    {
        foreach ($money->getMonies() as $containedMoney) {
            $currency = $containedMoney->getCurrency();
            $currencyCode = $currency->getCurrencyCode();

            $this->monies[$currencyCode] = $this->getMoney($currency)->minus($containedMoney);
        }

        return $this;
    }
}
