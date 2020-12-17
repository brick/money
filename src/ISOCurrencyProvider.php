<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\Exception\UnknownCurrencyException;

/**
 * Provides ISO 4217 currencies.
 */
final class ISOCurrencyProvider
{
    /**
     * @var ISOCurrencyProvider|null
     */
    private static $instance;

    /**
     * The raw currency data, indexed by currency code.
     *
     * @psalm-var array<string, array{string, int, string, int}>
     *
     * @var array
     */
    private $currencyData;

    /**
     * An associative array of currency numeric code to currency code.
     *
     * This property is set on-demand, as soon as required.
     *
     * @psalm-var array<int, string>|null
     *
     * @var array|null
     */
    private $numericToCurrency;

    /**
     * An associative array of country code to currency codes.
     *
     * This property is set on-demand, as soon as required.
     *
     * @psalm-var array<string, list<string>>|null
     *
     * @var array|null
     */
    private $countryToCurrency;

    /**
     * The Currency instances.
     *
     * The instances are created on-demand, as soon as they are requested.
     *
     * @psalm-var array<string, Currency>
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
    public static function getInstance() : ISOCurrencyProvider
    {
        if (self::$instance === null) {
            self::$instance = new ISOCurrencyProvider();
        }

        return self::$instance;
    }

    /**
     * Returns the currency matching the given currency code.
     *
     * @param string|int $currencyCode The 3-letter or numeric ISO 4217 currency code.
     *
     * @return Currency The currency.
     *
     * @throws UnknownCurrencyException If the currency code is not known.
     */
    public function getCurrency($currencyCode) : Currency
    {
        if (is_int($currencyCode)) {
            if ($this->numericToCurrency === null) {
                $this->numericToCurrency = require __DIR__ . '/../data/numeric-to-currency.php';
            }

            if (isset($this->numericToCurrency[$currencyCode])) {
                return $this->getCurrency($this->numericToCurrency[$currencyCode]);
            }

            throw UnknownCurrencyException::unknownCurrency($currencyCode);
        }

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
    public function getAvailableCurrencies() : array
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
     * @param string $countryCode The 2-letter ISO 3166-1 country code.
     *
     * @return Currency
     *
     * @throws UnknownCurrencyException If the country code is not known, or the country has no single currency.
     */
    public function getCurrencyForCountry(string $countryCode) : Currency
    {
        $currencies = $this->getCurrenciesForCountry($countryCode);

        $count = count($currencies);

        if ($count === 1) {
            return $currencies[0];
        }

        if ($count === 0) {
            throw UnknownCurrencyException::noCurrencyForCountry($countryCode);
        }

        $currencyCodes = [];

        foreach ($currencies as $currency) {
            $currencyCodes[] = $currency->getCurrencyCode();
        }

        throw UnknownCurrencyException::noSingleCurrencyForCountry($countryCode, $currencyCodes);
    }

    /**
     * Returns the currencies for the given ISO country code.
     *
     * If the country code is not known, or if the country has no official currency, an empty array is returned.
     *
     * @param string $countryCode The 2-letter ISO 3166-1 country code.
     *
     * @return Currency[]
     */
    public function getCurrenciesForCountry(string $countryCode) : array
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
