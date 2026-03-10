<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\MathException;
use Brick\Money\Exception\UnknownCurrencyException;
use Closure;
use JsonSerializable;
use Override;

use function array_keys;
use function array_map;
use function array_values;
use function ksort;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * Container for monies in different currencies.
 *
 * This class is immutable. The bag never stores zero-valued slots: when an arithmetic operation reduces a
 * currency's net amount to zero, that slot is removed. A currency is present in the bag if and only if its
 * net amount is non-zero.
 */
final readonly class MoneyBag implements Monetary, JsonSerializable
{
    /**
     * @param array<string, RationalMoney> $monies The monies in this bag, indexed and sorted by currency code.
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
     * @deprecated Use of() instead.
     */
    public static function fromMonies(Monetary ...$monies): MoneyBag
    {
        trigger_error('MoneyBag::fromMonies() is deprecated. Use MoneyBag::of() instead.', E_USER_DEPRECATED);

        if ($monies === []) {
            return new MoneyBag();
        }

        return self::of(...$monies);
    }

    /**
     * Returns a MoneyBag containing the given monies.
     *
     * Monies in the same currency are added together into a single slot.
     * If no arguments are given, an empty MoneyBag is returned.
     *
     * @pure
     */
    public static function of(Monetary ...$monies): MoneyBag
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
     * If this bag does not contain an amount in the given currency, a zero RationalMoney is returned.
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

    /**
     * Returns whether this MoneyBag has zero value (i.e. contains no non-zero amounts).
     *
     * @pure
     */
    public function isZero(): bool
    {
        return $this->monies === [];
    }

    /**
     * Returns whether this MoneyBag is equal to the given monetary value.
     *
     * Two values are equal if they contain exactly the same set of currencies with the same amount in each.
     *
     * Zero amounts in different currencies compare equal by this method. This differs from AbstractMoney::isEqualTo(),
     * which treats currency as part of the value's identity even when the amount is zero.
     *
     * @pure
     */
    public function isEqualTo(Monetary $that): bool
    {
        if (! $that instanceof MoneyBag) {
            $that = MoneyBag::of($that);
        }

        if (array_keys($this->monies) !== array_keys($that->monies)) {
            return false;
        }

        foreach ($this->monies as $currencyCode => $money) {
            if (! $money->getAmount()->isEqualTo($that->monies[$currencyCode]->getAmount())) {
                return false;
            }
        }

        return true;
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
     * Returns a MoneyBag with each amount negated.
     *
     * @pure
     */
    public function negated(): MoneyBag
    {
        $monies = array_map(fn ($money) => $money->negated(), $this->monies);

        return new MoneyBag($monies);
    }

    /**
     * Returns a MoneyBag with each amount multiplied by the given number.
     *
     * If the factor is zero, an empty MoneyBag is returned.
     *
     * @param BigNumber|int|string $that The multiplier.
     *
     * @throws MathException If the argument is an invalid number.
     *
     * @pure
     */
    public function multipliedBy(BigNumber|int|string $that): MoneyBag
    {
        $that = BigNumber::of($that);

        if ($that->isZero()) {
            return new MoneyBag();
        }

        $monies = array_map(fn ($money) => $money->multipliedBy($that), $this->monies);

        return new MoneyBag($monies);
    }

    /**
     * Returns a MoneyBag with each amount divided by the given number.
     *
     * @param BigNumber|int|string $that The divisor.
     *
     * @throws MathException           If the argument is an invalid number.
     * @throws DivisionByZeroException If the argument is zero.
     *
     * @pure
     */
    public function dividedBy(BigNumber|int|string $that): MoneyBag
    {
        $that = BigNumber::of($that);

        if ($that->isZero()) {
            throw DivisionByZeroException::divisionByZero();
        }

        $monies = array_map(fn ($money) => $money->dividedBy($that), $this->monies);

        return new MoneyBag($monies);
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
        $sort = false;

        foreach ($money->getMonies() as $containedMoney) {
            $currency = $containedMoney->getCurrency();
            $currencyCode = $currency->getCurrencyCode();

            $result = $fn(self::get($monies, $currency), $containedMoney);

            if ($result->isZero()) {
                unset($monies[$currencyCode]);
            } else {
                $sort = $sort || ! isset($monies[$currencyCode]);
                $monies[$currencyCode] = $result;
            }
        }

        if ($sort) {
            // @phpstan-ignore possiblyImpure.functionCall
            ksort($monies);
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
