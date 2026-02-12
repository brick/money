<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigRational;
use Override;

use function array_values;
use function is_int;
use function trigger_deprecation;

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
     * Returns the amount in the given currency contained in the bag, as a rational number.
     *
     * Non-ISO (non-numeric) currency codes are accepted.
     *
     * @deprecated Use getMoney()->getAmount() instead. Note that getMoney() does not support non-ISO currency codes,
     *             and does not support numeric currency codes.
     *
     * @param Currency|string|int $currency The Currency instance, currency code or ISO numeric currency code.
     */
    public function getAmount(Currency|string|int $currency): BigRational
    {
        trigger_deprecation('brick/money', '0.11.0', 'Calling "%s()" is deprecated, use getMoney()->getAmount() instead.', __METHOD__);

        if ($currency instanceof Currency) {
            $currencyCode = $currency->getCurrencyCode();
        } elseif (is_int($currency)) {
            $currencyCode = Currency::ofNumericCode($currency)->getCurrencyCode();
        } else {
            $currencyCode = $currency;
        }

        if (isset($this->monies[$currencyCode])) {
            return $this->monies[$currencyCode]->getAmount();
        }

        return BigRational::zero();
    }

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
