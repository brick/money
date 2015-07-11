<?php

namespace Brick\Money;

use Brick\Money\Exception\CurrencyConversionException;

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
class MoneyComparator
{
    /**
     * The exchange rate provider.
     *
     * @var ExchangeRateProvider
     */
    private $exchangeRateProvider;

    /**
     * Class constructor.
     *
     * @param ExchangeRateProvider $provider The exchange rate provider.
     */
    public function __construct(ExchangeRateProvider $provider)
    {
        $this->exchangeRateProvider = $provider;
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
     * @param Money $a
     * @param Money $b
     *
     * @return int -1, 0 or 1.
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     */
    public function compare(Money $a, Money $b)
    {
        $aCurrency = $a->getCurrency();
        $bCurrency = $b->getCurrency();

        if ($aCurrency->is($bCurrency)) {
            return $a->compareTo($b);
        }

        $aAmount = $a->getAmount();
        $bAmount = $b->getAmount();

        $exchangeRate = $this->exchangeRateProvider->getExchangeRate($aCurrency, $bCurrency);

        $aAmount = $aAmount->toBigRational()->multipliedBy($exchangeRate);

        return $aAmount->compareTo($bAmount);
    }

    /**
     * @param Money $a
     * @param Money $b
     *
     * @return bool
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     */
    public function isEqual(Money $a, Money $b)
    {
        return $this->compare($a, $b) == 0;
    }

    /**
     * @param Money $a
     * @param Money $b
     *
     * @return bool
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     */
    public function isLess(Money $a, Money $b)
    {
        return $this->compare($a, $b) < 0;
    }

    /**
     * @param Money $a
     * @param Money $b
     *
     * @return bool
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     */
    public function isLessOrEqual(Money $a, Money $b)
    {
        return $this->compare($a, $b) <= 0;
    }

    /**
     * @param Money $a
     * @param Money $b
     *
     * @return bool
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     */
    public function isGreater(Money $a, Money $b)
    {
        return $this->compare($a, $b) > 0;
    }

    /**
     * @param Money $a
     * @param Money $b
     *
     * @return bool
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     */
    public function isGreaterOrEqual(Money $a, Money $b)
    {
        return $this->compare($a, $b) >= 0;
    }

    /**
     * Returns the smallest of the given monies.
     *
     * The monies are compared from left to right. This distinction can be important if the exchange rate provider
     * does not have bidirectional exchange rates.
     *
     * For example, when comparing [A, B, C], this method will first compare A against B, then min(A,B) against C.
     *
     * If several monies are equal to the minimum value, the first one is returned.
     *
     * @param Money ...$monies The monies to compare.
     *
     * @return Money The smallest Money.
     */
    public function min(Money ...$monies)
    {
        $min = null;

        foreach ($monies as $money) {
            if ($min === null || $this->isGreater($min, $money)) {
                $min = $money;
            }
        }

        if ($min === null) {
            throw new \InvalidArgumentException('min() expects at least one Money.');
        }

        return $min;
    }

    /**
     * Returns the larget of the given monies.
     *
     * The monies are compared from left to right. This distinction can be important if the exchange rate provider
     * does not have bidirectional exchange rates.
     *
     * For example, when comparing [A, B, C], this method will first compare A against B, then max(A,B) against C.
     *
     * If several monies are equal to the maximum value, the first one is returned.
     *
     * @param Money ...$monies The monies to compare.
     *
     * @return Money The largest Money.
     */
    public function max(Money ...$monies)
    {
        $max = null;

        foreach ($monies as $money) {
            if ($max === null || $this->isLess($max, $money)) {
                $max = $money;
            }
        }

        if ($max === null) {
            throw new \InvalidArgumentException('max() expects at least one Money.');
        }

        return $max;
    }
}
