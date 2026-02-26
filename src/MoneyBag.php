<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigRational;
use Brick\Money\Exception\UnknownCurrencyException;
use Closure;
use JsonSerializable;
use Override;

use function array_map;
use function array_values;

/**
 * Container for monies in different currencies.
 *
 * This class is immutable.
 */
final readonly class MoneyBag implements Monetary, JsonSerializable
{
    /**
     * @param array<string, RationalMoney> $monies The monies in this bag, indexed by currency code.
     *
     * @pure
     */
    private function __construct(
        private array $monies = [],
    ) {
    }

    /**
     * Returns an empty MoneyBag of zero value.
     *
     * @pure
     */
    public static function zero(): MoneyBag
    {
        return new MoneyBag();
    }

    /**
     * Creates a MoneyBag from a list of monies.
     *
     * @pure
     */
    public static function fromMonies(Monetary ...$monies): MoneyBag
    {
        $result = [];

        foreach ($monies as $money) {
            $result = self::accumulate($result, $money, fn ($a, $b) => $a->plus($b));
        }

        return new MoneyBag($result);
    }

    /**
     * Returns the contained amount in the given currency as a RationalMoney.
     *
     * If no amount in the given currency has been added to this bag, a zero-valued RationalMoney is returned.
     *
     * @param Currency|string $currency The Currency instance, or ISO currency code.
     *
     * @throws UnknownCurrencyException If an unknown currency code is given.
     *
     * @pure
     */
    public function getMoney(Currency|string $currency): RationalMoney
    {
        $currency = $currency instanceof Currency ? $currency : Currency::of($currency);

        return self::get($this->monies, $currency);
    }

    #[Override]
    public function getMonies(): array
    {
        return array_values($this->monies);
    }

    /**
     * @return list<array{amount: string, currency: string}>
     *
     * @pure
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return array_map(
            static fn (RationalMoney $money) => $money->jsonSerialize(),
            array_values($this->monies),
        );
    }

    /**
     * Returns a MoneyBag with the given monetary amount added.
     *
     * @param Monetary $money A Money, RationalMoney, or MoneyBag instance.
     *
     * @return MoneyBag The new MoneyBag instance.
     *
     * @pure
     */
    public function plus(Monetary $money): MoneyBag
    {
        return new MoneyBag(self::accumulate($this->monies, $money, fn ($a, $b) => $a->plus($b)));
    }

    /**
     * Returns a MoneyBag with the given monetary amount subtracted.
     *
     * @param Monetary $money A Money, RationalMoney, or MoneyBag instance.
     *
     * @return MoneyBag The new MoneyBag instance.
     *
     * @pure
     */
    public function minus(Monetary $money): MoneyBag
    {
        return new MoneyBag(self::accumulate($this->monies, $money, fn ($a, $b) => $a->minus($b)));
    }

    /**
     * @param array<string, RationalMoney>                              $monies
     * @param pure-Closure(RationalMoney, RationalMoney): RationalMoney $fn
     *
     * @return array<string, RationalMoney>
     *
     * @pure
     */
    private static function accumulate(array $monies, Monetary $money, Closure $fn): array
    {
        foreach ($money->getMonies() as $containedMoney) {
            $currency = $containedMoney->getCurrency();
            $currencyCode = $currency->getCurrencyCode();

            $monies[$currencyCode] = $fn(self::get($monies, $currency), $containedMoney);
        }

        return $monies;
    }

    /**
     * @param array<string, RationalMoney> $monies
     *
     * @pure
     */
    private static function get(array $monies, Currency $currency): RationalMoney
    {
        $currencyCode = $currency->getCurrencyCode();

        if (isset($monies[$currencyCode])) {
            return $monies[$currencyCode];
        }

        return new RationalMoney(BigRational::zero(), $currency);
    }
}
