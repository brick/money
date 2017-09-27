<?php

namespace Brick\Money;

use Brick\Money\Exception\UnknownCurrencyException;

/**
 * Provides ISO 4217 currencies.
 */
class ISOCurrencyProvider
{
    /**
     * @var ISOCurrencyProvider|null
     */
    private static $instance;

    /**
     * The raw currency data, indexed by currency code.
     *
     * @var array
     */
    private $currencyData;

    /**
     * An associative array of country code to currency codes.
     *
     * This property is set on-demand, as soon as required.
     *
     * @var array|null
     */
    private $countryToCurrency;

    /**
     * The Currency instances.
     *
     * The instances are created on-demand, as soon as they are requested.
     *
     * @var Currency[]
     */
    private $currencies = [];

    /**
     * Whether the provider is in a partial state.
     *
     * This is true as long as all the currencies have not been instantiated yet.
     *
     * @var bool
     */
    private $isPartial = true;

    /**
     * Private constructor. Use `getInstance()` to obtain the singleton instance.
     */
    private function __construct()
    {
        $this->currencyData = require __DIR__ . '/../data/iso-currencies.php';
    }

    /**
     * Returns the singleton instance of ISOCurrencyProvider.
     *
     * @return ISOCurrencyProvider
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new ISOCurrencyProvider();
        }

        return self::$instance;
    }

    /**
     * Returns the currency matching the given currency code.
     *
     * @param string $currencyCode The ISO 4217 currency code.
     *
     * @return Currency The currency.
     *
     * @throws UnknownCurrencyException If the currency code is not known.
     */
    public function getCurrency($currencyCode)
    {
        if (isset($this->currencies[$currencyCode])) {
            return $this->currencies[$currencyCode];
        }

        if (! isset($this->currencyData[$currencyCode])) {
            throw UnknownCurrencyException::unknownCurrency($currencyCode);
        }

        $currency = new Currency(... $this->currencyData[$currencyCode]);

        return $this->currencies[$currencyCode] = $currency;
    }

    /**
     * Returns all the available currencies.
     *
     * @return Currency[] The currencies, indexed by currency code.
     */
    public function getAvailableCurrencies()
    {
        if ($this->isPartial) {
            foreach ($this->currencyData as $currencyCode => $data) {
                if (! isset($this->currencies[$currencyCode])) {
                    $this->currencies[$currencyCode] = new Currency(... $data);
                }
            }

            ksort($this->currencies);

            $this->isPartial = false;
        }

        return $this->currencies;
    }

    /**
     * Returns the currency for the given ISO country code.
     *
     * @param string $countryCode A 2-letter ISO 3166-1 country code.
     *
     * @return Currency
     *
     * @throws UnknownCurrencyException If the country code is not known, or the country has no single currency.
     */
    public function getCurrencyForCountry($countryCode)
    {
        $currencies = $this->getCurrenciesForCountry($countryCode);

        $count = count($currencies);

        if ($count === 1) {
            return $currencies[0];
        }

        if ($count === 0) {
            throw new UnknownCurrencyException('No currency found for ' . $countryCode);
        }

        $currencyCodes = [];

        foreach ($currencies as $currency) {
            $currencyCodes[] = $currency->getCurrencyCode();
        }

        throw new UnknownCurrencyException('Several currencies found for ' . $countryCode . ': ' . implode(', ', $currencyCodes));
    }

    /**
     * Returns the currencies for the given ISO country code.
     *
     * If the country code is not known, or if the country has no official currency, an empty array is returned.
     *
     * @param string $countryCode The 2-letter ISO 3166-1 country codes.
     *
     * @return Currency[]
     */
    public function getCurrenciesForCountry($countryCode)
    {
        if ($this->countryToCurrency === null) {
            $this->countryToCurrency = require __DIR__ . '/../data/country-to-currency.php';
        }

        $result = [];

        if (isset($this->countryToCurrency[$countryCode])) {
            foreach ($this->countryToCurrency[$countryCode] as $currencyCode) {
                $result[] = $this->getCurrency($currencyCode);
            }
        }

        return $result;
    }
}
