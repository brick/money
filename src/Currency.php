<?php

namespace Brick\Money;

use Brick\Money\CurrencyProvider\ISOCurrencyProvider;
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
    private function __construct($currencyCode, $numericCode, $name, $defaultFractionDigits)
    {
        $this->currencyCode          = $currencyCode;
        $this->numericCode           = $numericCode;
        $this->name                  = $name;
        $this->defaultFractionDigits = $defaultFractionDigits;
    }

    /**
     * Creates a Currency.
     *
     * @param string  $currencyCode          The currency code.
     * @param string  $numericCode           The numeric currency code.
     * @param string  $name                  The currency name.
     * @param int     $defaultFractionDigits The default number of fraction digits.
     *
     * @return Currency
     */
    public static function create($currencyCode, $numericCode, $name, $defaultFractionDigits)
    {
        $currencyCode          = (string) $currencyCode;
        $numericCode           = (string) $numericCode;
        $name                  = (string) $name;
        $defaultFractionDigits = (int) $defaultFractionDigits;

        if (preg_match('/^[a-zA-Z0-9]+$/', $currencyCode) !== 1) {
            throw new \InvalidArgumentException('The currency code must be alphanumeric and non empty.');
        }

        if (! ctype_digit($numericCode)) {
            throw new \InvalidArgumentException('The numeric code must consist of digits only.');
        }

        if ($defaultFractionDigits < 0) {
            throw new \InvalidArgumentException('The default fraction digits cannot be less than zero.');
        }

        return new Currency($currencyCode, $numericCode, $name, $defaultFractionDigits);
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
