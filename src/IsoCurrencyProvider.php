<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\Exception\UnknownCurrencyException;

use function array_map;
use function count;
use function ksort;

/**
 * Provides ISO 4217 currencies.
 */
final class IsoCurrencyProvider
{
    private static ?IsoCurrencyProvider $instance = null;

    /**
     * The raw currency data, indexed by currency code.
     *
     * @var array<string, array{string, int, string, non-negative-int, CurrencyType}>
     */
    private readonly array $currencyData;

    /**
     * An associative array of currency numeric code to currency code.
     *
     * This property is set on-demand, as soon as required.
     *
     * @var array<int, string>|null
     */
    private ?array $numericToCurrency = null;

    /**
     * An associative array of country code to current currency codes.
     *
     * This property is set on-demand, as soon as required.
     *
     * @var array<string, list<string>>|null
     */
    private ?array $countryToCurrency = null;

    /**
     * An associative array of country code to currency codes.
     * Contains only historical currencies. The countries may no longer exist.
     *
     * This property is set on-demand, as soon as required.
     *
     * @var array<string, list<string>>|null
     */
    private ?array $countryToCurrencyHistorical = null;

    /**
     * The Currency instances.
     *
     * The instances are created on-demand, as soon as they are requested.
     *
     * @var array<string, Currency>
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
     * Returns the singleton instance of IsoCurrencyProvider.
     */
    public static function getInstance(): IsoCurrencyProvider
    {
        if (self::$instance === null) {
            self::$instance = new IsoCurrencyProvider();
        }

        return self::$instance;
    }

    /**
     * Returns the currency matching the given currency code.
     *
     * @param string $currencyCode The 3-letter uppercase ISO 4217 currency code.
     *
     * @return Currency The currency.
     *
     * @throws UnknownCurrencyException If the currency code is not known.
     */
    public function getCurrency(string $currencyCode): Currency
    {
        if (isset($this->currencies[$currencyCode])) {
            return $this->currencies[$currencyCode];
        }

        if (! isset($this->currencyData[$currencyCode])) {
            throw UnknownCurrencyException::unknownCurrency($currencyCode);
        }

        $currency = new Currency(...$this->currencyData[$currencyCode]);

        return $this->currencies[$currencyCode] = $currency;
    }

    /**
     * Returns a Currency instance matching the given ISO currency code.
     *
     * Note: Numeric codes often mirror the ISO 3166-1 numeric code of the issuing
     * country/territory, so they may outlive a particular currency and be kept/reused
     * across currency changes. The resolved Currency therefore depends on the ISO 4217
     * dataset version and may change after an update in a minor version.
     *
     * @param int $currencyCode The numeric ISO 4217 currency code.
     *
     * @return Currency The currency.
     *
     * @throws UnknownCurrencyException If the currency code is not known.
     */
    public function getCurrencyByNumericCode(int $currencyCode): Currency
    {
        if ($this->numericToCurrency === null) {
            $this->numericToCurrency = require __DIR__ . '/../data/numeric-to-currency.php';
        }

        if (isset($this->numericToCurrency[$currencyCode])) {
            return $this->getCurrency($this->numericToCurrency[$currencyCode]);
        }

        throw UnknownCurrencyException::unknownCurrency($currencyCode);
    }

    /**
     * Returns all the available currencies.
     *
     * @return array<string, Currency> The currencies, indexed by currency code.
     */
    public function getAvailableCurrencies(): array
    {
        if ($this->isPartial) {
            foreach ($this->currencyData as $currencyCode => $data) {
                if (! isset($this->currencies[$currencyCode])) {
                    $this->currencies[$currencyCode] = new Currency(...$data);
                }
            }

            ksort($this->currencies);

            $this->isPartial = false;
        }

        return $this->currencies;
    }

    /**
     * Returns the current currency for the given ISO country code.
     *
     * Note: This value may change in minor releases, as countries may change their official currency.
     *
     * @param string $countryCode The 2-letter ISO 3166-1 country code.
     *
     * @throws UnknownCurrencyException If the country code is not known, or the country has no single currency.
     */
    public function getCurrencyForCountry(string $countryCode): Currency
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
     * Returns the current currencies for the given ISO country code.
     *
     * If the country code is not known, or if the country has no official currency, an empty array is returned.
     *
     * Note: This value may change in minor releases, as countries may change their official currencies.
     *
     * @param string $countryCode The 2-letter ISO 3166-1 country code.
     *
     * @return Currency[]
     */
    public function getCurrenciesForCountry(string $countryCode): array
    {
        if ($this->countryToCurrency === null) {
            $this->countryToCurrency = require __DIR__ . '/../data/country-to-currency.php';
        }

        if (isset($this->countryToCurrency[$countryCode])) {
            return array_map(
                $this->getCurrency(...),
                $this->countryToCurrency[$countryCode],
            );
        }

        return [];
    }

    /**
     * Returns the historical currencies for the given ISO country code.
     *
     * If the country code is not known, or if the country has no official currency, an empty array is returned.
     *
     * Note: This value may change in minor releases, as additional currencies can be withdrawn from countries.
     *
     * @param string $countryCode The 2-letter ISO 3166-1 country code.
     *
     * @return Currency[]
     */
    public function getHistoricalCurrenciesForCountry(string $countryCode): array
    {
        if ($this->countryToCurrencyHistorical === null) {
            $this->countryToCurrencyHistorical = require __DIR__ . '/../data/country-to-currency-historical.php';
        }

        if (isset($this->countryToCurrencyHistorical[$countryCode])) {
            return array_map(
                $this->getCurrency(...),
                $this->countryToCurrencyHistorical[$countryCode],
            );
        }

        return [];
    }
}
