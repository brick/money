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
    /**
     * @param ExchangeRateProvider $exchangeRateProvider The exchange rate provider.
     */
    public function __construct(
        private ExchangeRateProvider $exchangeRateProvider,
    ) {
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
    public function compare(Money $a, Money $b): int
    {
        $aCurrency = $a->getCurrency();
        $bCurrency = $b->getCurrency();

        if ($aCurrency->isEqualTo($bCurrency)) {
            return $a->compareTo($b);
        }

        $aAmount = $a->getAmount();
        $bAmount = $b->getAmount();

        $exchangeRate = $this->exchangeRateProvider->getExchangeRate($aCurrency, $bCurrency);

        if ($exchangeRate === null) {
            throw ExchangeRateNotFoundException::exchangeRateNotFound(
                $aCurrency->getCurrencyCode(),
                $bCurrency->getCurrencyCode(),
            );
        }

        $aAmount = $aAmount->toBigRational()->multipliedBy($exchangeRate);

        return $aAmount->compareTo($bAmount);
    }

    /**
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function isEqual(Money $a, Money $b): bool
    {
        return $this->compare($a, $b) === 0;
    }

    /**
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function isLess(Money $a, Money $b): bool
    {
        return $this->compare($a, $b) < 0;
    }

    /**
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function isLessOrEqual(Money $a, Money $b): bool
    {
        return $this->compare($a, $b) <= 0;
    }

    /**
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function isGreater(Money $a, Money $b): bool
    {
        return $this->compare($a, $b) > 0;
    }

    /**
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function isGreaterOrEqual(Money $a, Money $b): bool
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
     * @param Money $money     The first money.
     * @param Money ...$monies The subsequent monies.
     *
     * @return Money The smallest Money.
     *
     * @throws ExchangeRateNotFoundException If an exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function min(Money $money, Money ...$monies): Money
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
     * @param Money $money     The first money.
     * @param Money ...$monies The subsequent monies.
     *
     * @return Money The largest Money.
     *
     * @throws ExchangeRateNotFoundException If an exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function max(Money $money, Money ...$monies): Money
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
