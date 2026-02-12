<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigRational;
use Override;
use ReflectionClass;

use function array_values;
use function trigger_error;

use const E_USER_DEPRECATED;

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

    public function __construct()
    {
        trigger_error(
            'Instantiating a MoneyBag with new is deprecated, and will be disallowed in a future version. ' .
            'Use MoneyBag::zero(), or create from a list of monies using MoneyBag::fromMonies().',
            E_USER_DEPRECATED,
        );
    }

    /**
     * Returns an empty MoneyBag of zero value.
     */
    public static function zero(): MoneyBag
    {
        // Temporary fix to bypass the deprecation warning in the constructor.
        // This will be removed in a future version.
        return (new ReflectionClass(MoneyBag::class))->newInstanceWithoutConstructor();
    }

    /**
     * Creates a MoneyBag from a list of monies.
     */
    public static function fromMonies(Monetary ...$monies): MoneyBag
    {
        $moneyBag = MoneyBag::zero();

        foreach ($monies as $money) {
            $moneyBag = $moneyBag->plus($money);
        }

        return $moneyBag;
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
     * Returns a MoneyBag with the given monetary amount added.
     *
     * @param Monetary $money A Money, RationalMoney, or MoneyBag instance.
     *
     * @return MoneyBag The new MoneyBag instance.
     */
    public function plus(Monetary $money): MoneyBag
    {
        $new = clone $this;

        foreach ($money->getMonies() as $containedMoney) {
            $currency = $containedMoney->getCurrency();
            $currencyCode = $currency->getCurrencyCode();

            $new->monies[$currencyCode] = $new->getMoney($currency)->plus($containedMoney);
        }

        return $new;
    }

    /**
     * Returns a MoneyBag with the given monetary amount subtracted.
     *
     * @param Monetary $money A Money, RationalMoney, or MoneyBag instance.
     *
     * @return MoneyBag The new MoneyBag instance.
     */
    public function minus(Monetary $money): MoneyBag
    {
        $new = clone $this;

        foreach ($money->getMonies() as $containedMoney) {
            $currency = $containedMoney->getCurrency();
            $currencyCode = $currency->getCurrencyCode();

            $new->monies[$currencyCode] = $new->getMoney($currency)->minus($containedMoney);
        }

        return $new;
    }

    /**
     * Adds money to this bag.
     *
     * @deprecated MoneyBag will be made immutable in a future version. Use MoneyBag::plus() instead, which returns a new instance.
     *
     * @param Monetary $money A Money, RationalMoney, or MoneyBag instance.
     *
     * @return MoneyBag This instance.
     */
    public function add(Monetary $money): MoneyBag
    {
        trigger_error(
            'MoneyBag::add() is deprecated, and will be removed in a future version. ' .
            'MoneyBag will be immutable in the future. Use MoneyBag::plus(), which returns a new instance instead.',
            E_USER_DEPRECATED,
        );

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
     * @deprecated MoneyBag will be made immutable in a future version. Use MoneyBag::minus() instead, which returns a new instance.
     *
     * @param Monetary $money A Money, RationalMoney, or MoneyBag instance.
     *
     * @return MoneyBag This instance.
     */
    public function subtract(Monetary $money): MoneyBag
    {
        trigger_error(
            'MoneyBag::subtract() is deprecated, and will be removed in a future version. ' .
            'MoneyBag will be immutable in the future. Use MoneyBag::minus(), which returns a new instance instead.',
            E_USER_DEPRECATED,
        );

        foreach ($money->getMonies() as $containedMoney) {
            $currency = $containedMoney->getCurrency();
            $currencyCode = $currency->getCurrencyCode();

            $this->monies[$currencyCode] = $this->getMoney($currency)->minus($containedMoney);
        }

        return $this;
    }
}
