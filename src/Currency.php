<?php

namespace Brick\Money;

use Brick\Money\ISOCurrencyProvider;
use Brick\Money\Exception\UnknownCurrencyException;

/**
 * A currency. This class is immutable.
 */
class Currency
{
    /**
     * The currency code.
     *
     * For ISO currencies this will be the 3-letter uppercase ISO 4217 currency code.
     * For non ISO currencies this can be any non-empty string of ASCII letters and digits.
     *
     * @var string
     */
    private $currencyCode;

    /**
     * The numeric currency code.
     *
     * For ISO currencies this will be the 3-digit ISO 4217 numeric currency code.
     * For non ISO currencies this can be any non-empty string of digits, typically '0' if unused.
     *
     * @var string
     */
    private $numericCode;

    /**
     * The name of the currency.
     *
     * For ISO currencies this will be the official English name of the currency.
     * For non ISO currencies no constraints are defined.
     *
     * @var string
     */
    private $name;

    /**
     * The default number of fraction digits (typical scale) used with this currency.
     *
     * For example, the default number of fraction digits for the Euro is 2, while for the Japanese Yen it is 0.
     * This cannot be a negative number.
     *
     * @var int
     */
    private $defaultFractionDigits;

    /**
     * Private constructor. Use getInstance() to obtain an instance.
     *
     * @param string $currencyCode          The currency code.
     * @param string $numericCode           The numeric currency code.
     * @param string $name                  The currency name.
     * @param int    $defaultFractionDigits The default number of fraction digits.
     */
    public function __construct($currencyCode, $numericCode, $name, $defaultFractionDigits)
    {
        $defaultFractionDigits = (int) $defaultFractionDigits;

        if ($defaultFractionDigits < 0) {
            throw new \InvalidArgumentException('The default fraction digits cannot be less than zero.');
        }

        $this->currencyCode          = (string) $currencyCode;
        $this->numericCode           = (string) $numericCode;
        $this->name                  = (string) $name;
        $this->defaultFractionDigits = $defaultFractionDigits;
    }

    /**
     * Returns a Currency instance matching the given ISO currency code.
     *
     * If a Currency instance is provided, it is returned as is.
     *
     * @param Currency|string $currency The ISO 4217 currency code, or a Currency instance.
     *
     * @return Currency
     *
     * @throws UnknownCurrencyException If an unknown currency code is given.
     */
    public static function of($currency)
    {
        if ($currency instanceof Currency) {
            return $currency;
        }

        return ISOCurrencyProvider::getInstance()->getCurrency($currency);
    }

    /**
     * Returns a Currency instance for the given ISO country code.
     *
     * @param string $countryCode A 2-letter ISO 3166-1 country code.
     *
     * @return Currency
     *
     * @throws UnknownCurrencyException If the country code is unknown, or there is no single currency for the country.
     */
    public static function ofCountry($countryCode)
    {
        return ISOCurrencyProvider::getInstance()->getCurrencyForCountry($countryCode);
    }

    /**
     * Returns the currency code.
     *
     * For ISO currencies this will be the 3-letter uppercase ISO 4217 currency code.
     * For non ISO currencies this can be any non-empty string of ASCII letters and digits.
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }

    /**
     * Returns the numeric currency code.
     *
     * For ISO currencies this will be the 3-digit ISO 4217 numeric currency code.
     * For non ISO currencies this can be any non-empty string of digits, typically '0' if unused.
     *
     * @return string
     */
    public function getNumericCode()
    {
        return $this->numericCode;
    }

    /**
     * Returns the name of the currency.
     *
     * For ISO currencies this will be the official English name of the currency.
     * For non ISO currencies no constraints are defined.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the default number of fraction digits (typical scale) used with this currency.
     *
     * For example, the default number of fraction digits for the Euro is 2, while for the Japanese Yen it is 0.
     *
     * @return int
     */
    public function getDefaultFractionDigits()
    {
        return $this->defaultFractionDigits;
    }

    /**
     * Returns whether this currency is equal to the given currency.
     *
     * The currencies are considered equal if their currency codes are equal.
     *
     * @param Currency|string $currency The currency to check, as a Currency instance or ISO currency code.
     *
     * @return bool
     */
    public function is($currency)
    {
        return $this->currencyCode === (string) $currency;
    }

    /**
     * Returns the currency code.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->currencyCode;
    }
}
