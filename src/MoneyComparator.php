<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\Exception\ExchangeRateNotFoundException;
use Brick\Money\Exception\ExchangeRateProviderException;

/**
 * Compares monies in different currencies.
 *
 * The converted amounts are never rounded before comparison, so this comparator is more precise
 * than converting a Money to another Currency, then using the resulting Money's built-in comparison methods.
 *
 * Note that the comparison is always performed by converting the first Money into the currency of the second Money.
 * This order is important because some exchange rate providers may only have one-way rates,
 * or may use a different rate in each direction.
 */
final readonly class MoneyComparator
{
    private CurrencyConverter $currencyConverter;

    /**
     * @param array<string, mixed> $dimensions Additional exchange-rate lookup dimensions (e.g., date or rate type).
     */
    public function __construct(
        ExchangeRateProvider $exchangeRateProvider,
        private array $dimensions = [],
    ) {
        $this->currencyConverter = new CurrencyConverter($exchangeRateProvider);
    }

    /**
     * Compares the given monies.
     *
     * The amount is not rounded before comparison, so the results are more relevant than when using
     * `convert($a, $b->getCurrency())->compareTo($b)`.
     *
     * Note that the comparison is performed by converting A into B's currency.
     * This order is important if the exchange rate provider uses different exchange rates
     * when converting back and forth two currencies.
     *
     * @return -1|0|1
     *
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function compare(AbstractMoney $a, AbstractMoney $b): int
    {
        return $this->currencyConverter->convertToRational($a, $b->getCurrency(), $this->dimensions)->compareTo($b);
    }

    /**
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function isEqual(AbstractMoney $a, AbstractMoney $b): bool
    {
        return $this->compare($a, $b) === 0;
    }

    /**
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function isLess(AbstractMoney $a, AbstractMoney $b): bool
    {
        return $this->compare($a, $b) < 0;
    }

    /**
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function isLessOrEqual(AbstractMoney $a, AbstractMoney $b): bool
    {
        return $this->compare($a, $b) <= 0;
    }

    /**
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function isGreater(AbstractMoney $a, AbstractMoney $b): bool
    {
        return $this->compare($a, $b) > 0;
    }

    /**
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function isGreaterOrEqual(AbstractMoney $a, AbstractMoney $b): bool
    {
        return $this->compare($a, $b) >= 0;
    }

    /**
     * Returns the smallest of the given monies.
     *
     * The monies are compared from left to right. This distinction can be important if the exchange rate provider does
     * not have bidirectional exchange rates, or applies different rates depending on the direction of the conversion.
     *
     * For example, when comparing [A, B, C], this method will first compare A against B, then min(A,B) against C.
     *
     * If several monies are equal to the minimum value, the first one is returned.
     *
     * @template T of AbstractMoney
     *
     * @param T $money     The first money.
     * @param T ...$monies The subsequent monies.
     *
     * @return T The smallest money.
     *
     * @throws ExchangeRateNotFoundException If an exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function min(AbstractMoney $money, AbstractMoney ...$monies): AbstractMoney
    {
        $min = $money;

        foreach ($monies as $money) {
            if ($this->isGreater($min, $money)) {
                $min = $money;
            }
        }

        return $min;
    }

    /**
     * Returns the largest of the given monies.
     *
     * The monies are compared from left to right. This distinction can be important if the exchange rate provider does
     * not have bidirectional exchange rates, or applies different rates depending on the direction of the conversion.
     *
     * For example, when comparing [A, B, C], this method will first compare A against B, then max(A,B) against C.
     *
     * If several monies are equal to the maximum value, the first one is returned.
     *
     * @template T of AbstractMoney
     *
     * @param T $money     The first money.
     * @param T ...$monies The subsequent monies.
     *
     * @return T The largest money.
     *
     * @throws ExchangeRateNotFoundException If an exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function max(AbstractMoney $money, AbstractMoney ...$monies): AbstractMoney
    {
        $max = $money;

        foreach ($monies as $money) {
            if ($this->isLess($max, $money)) {
                $max = $money;
            }
        }

        return $max;
    }
}
