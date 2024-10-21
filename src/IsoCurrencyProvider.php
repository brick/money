<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\Exception\UnknownIsoCurrencyException;

/**
 * Provides ISO 4217 currencies.
 */
final class IsoCurrencyProvider implements CurrencyProvider
{
    private static ?IsoCurrencyProvider $instance = null;

    /**
     * The raw currency data, indexed by currency code.
     *
     * @psalm-var array<string, array{string, int, string, int}>
     */
    private readonly array $currencyData;

    /**
     * An associative array of currency numeric code to currency code.
     *
     * This property is set on-demand, as soon as required.
     *
     * @psalm-var array<int, string>
     */
    private array $numericToCurrency = [];

    /**
     * An associative array of country code to currency codes.
     *
     * This property is set on-demand, as soon as required.
     *
     * @psalm-var array<string, list<string>>
     */
    private array $countryToCurrency = [];

    /**
     * The Currency instances.
     *
     * The instances are created on-demand, as soon as they are requested.
     *
     * @psalm-var array<string, IsoCurrency>
     *
     * @var IsoCurrency[]
     */
    private array $currencies = [];

    /**
     * Whether the provider is in a partial state.
     *
     * This is true as long as all the currencies have not been instantiated yet.
     */
    private bool $isPartial = true;

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
     * @return IsoCurrencyProvider
     */
    public static function getInstance() : IsoCurrencyProvider
    {
        if (self::$instance === null) {
            self::$instance = new IsoCurrencyProvider();
        }

        return self::$instance;
    }

    /**
     * Returns the currency matching the given currency code.
     *
     * @param string|int $code The 3-letter or numeric ISO 4217 currency code.
     *
     * @return IsoCurrency The currency.
     *
     * @throws UnknownIsoCurrencyException If the currency code is not known.
     */
    public function getByCode(string|int $code) : IsoCurrency
    {
        $currency = $this->findByCode($code);

        if ($currency === null) {
            if (is_int($code)) {
                throw UnknownIsoCurrencyException::unknownCurrency(null, $code);
            }

            throw UnknownIsoCurrencyException::unknownCurrency($code);
        }

        return $currency;
    }

    public function hasCode(?string $currencyCode = null, ?int $numericCode = null) : bool
    {
        if ($currencyCode === null && $numericCode === null) {
            throw new \InvalidArgumentException('At least one of currency code or numeric code passed must be provided');
        }

        if ($numericCode !== null) {
            if ($this->numericToCurrency === []) {
                $this->numericToCurrency = require __DIR__ . '/../data/numeric-to-currency.php';
            }

            if (isset($this->numericToCurrency[$numericCode])) {
                if ($currencyCode !== null) {
                    return $currencyCode === $this->numericToCurrency[$numericCode] && $this->hasCode($this->numericToCurrency[$numericCode]);
                }

                return $this->hasCode($this->numericToCurrency[$numericCode]);
            }

            return false;
        }

        if (isset($this->currencies[$currencyCode])) {
            return true;
        }

        if (! isset($this->currencyData[$currencyCode])) {
            return false;
        }

        return true;
    }

    private function findByCode(string|int $currencyCode) : ?IsoCurrency {


        if (is_int($currencyCode)) {
            if (!$this->hasCode(null, $currencyCode)) {
                return null;
            }

            $currencyCode = $this->numericToCurrency[$currencyCode];
        }

        if (!$this->hasCode($currencyCode)) {
            return null;
        }

        if (isset($this->currencies[$currencyCode])) {
            return $this->currencies[$currencyCode];
        }

        return $this->currencies[$currencyCode] = new IsoCurrency(... $this->currencyData[$currencyCode]);
    }

    /**
     * Returns all the available currencies.
     *
     * @psalm-return array<string, IsoCurrency>
     *
     * @return IsoCurrency[] The currencies, indexed by currency code.
     */
    public function getAvailableCurrencies() : array
    {
        if ($this->isPartial) {
            foreach ($this->currencyData as $currencyCode => $data) {
                if (! isset($this->currencies[$currencyCode])) {
                    $this->currencies[$currencyCode] = new IsoCurrency(... $data);
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
     * @return IsoCurrency
     *
     * @throws UnknownIsoCurrencyException If the country code is not known, or the country has no single currency.
     */
    public function getByCountryCode(string $countryCode) : IsoCurrency
    {
        $currencies = $this->getCurrenciesForCountry($countryCode);

        $count = count($currencies);

        if ($count === 1) {
            return $currencies[0];
        }

        if ($count === 0) {
            throw UnknownIsoCurrencyException::noCurrencyForCountry($countryCode);
        }

        $currencyCodes = [];

        foreach ($currencies as $currency) {
            $currencyCodes[] = $currency->getCode();
        }

        throw UnknownIsoCurrencyException::noSingleCurrencyForCountry($countryCode, $currencyCodes);
    }

    /**
     * Returns the currencies for the given ISO country code.
     *
     * If the country code is not known, or if the country has no official currency, an empty array is returned.
     *
     * @param string $countryCode The 2-letter ISO 3166-1 country code.
     *
     * @return IsoCurrency[]
     */
    public function getCurrenciesForCountry(string $countryCode) : array
    {
        if ($this->countryToCurrency === []) {
            $this->countryToCurrency = require __DIR__ . '/../data/country-to-currency.php';
        }

        $result = [];

        if (isset($this->countryToCurrency[$countryCode])) {
            foreach ($this->countryToCurrency[$countryCode] as $currencyCode) {
                $result[] = $this->getByCode($currencyCode);
            }
        }

        return $result;
    }
}
