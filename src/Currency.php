<?php

namespace Brick\Money;

use Brick\Money\CurrencyProvider\ISOCurrencyProvider;
use Brick\Money\Exception\UnknownCurrencyException;

/**
 * A currency as defined by ISO 4217.
 */
class Currency
{
    /**
     * The ISO 4217 alphabetic currency code.
     *
     * @var string
     */
    private $currencyCode;

    /**
     * The ISO 4217 numeric currency code.
     *
     * @var int
     */
    private $numericCode;

    /**
     * The english name of the currency.
     *
     * @var string
     */
    private $name;

    /**
     * The default number of fraction digits used with this currency.
     *
     * @var int
     */
    private $defaultFractionDigits;

    /**
     * Private constructor. Use getInstance() to obtain an instance.
     *
     * @param string  $currencyCode          The ISO 4217 alphabetic currency code.
     * @param int     $numericCode           The ISO 4217 numeric currency code.
     * @param string  $name                  The English currency name.
     * @param int     $defaultFractionDigits The default number of fraction digits.
     */
    private function __construct($currencyCode, $numericCode, $name, $defaultFractionDigits)
    {
        $this->currencyCode          = $currencyCode;
        $this->numericCode           = $numericCode;
        $this->name                  = $name;
        $this->defaultFractionDigits = $defaultFractionDigits;
    }

    /**
     * @param string $currencyCode
     * @param int    $numericCode
     * @param string $name
     * @param int    $defaultFractionDigits
     *
     * @return Currency
     */
    public static function create($currencyCode, $numericCode, $name, $defaultFractionDigits)
    {
        $currencyCode          = (string) $currencyCode;
        $numericCode           = (int) $numericCode;
        $name                  = (string) $name;
        $defaultFractionDigits = (int) $defaultFractionDigits;

        if ($defaultFractionDigits < 0) {
            throw new \InvalidArgumentException('The default fraction digits cannot be less than zero.');
        }

        return new Currency($currencyCode, $numericCode, $name, $defaultFractionDigits);
    }

    /**
     * Returns the Currency instance for the given currency code.
     *
     * @param Currency|string $currency
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
     * Returns all the available currencies.
     *
     * @return Currency[]
     */
    public static function getAvailableCurrencies()
    {
        return ISOCurrencyProvider::getInstance()->getAvailableCurrencies();
    }

    /**
     * Returns the ISO 4217 currency code of this currency.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->currencyCode;
    }

    /**
     * @return int
     */
    public function getNumericCode()
    {
        return $this->numericCode;
    }

    /**
     * Returns the english name of this currency.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the default number of fraction digits used with this currency.
     *
     * For example, the default number of fraction digits for the Euro is 2, while for the Japanese Yen it's 0.
     * In the case of pseudo-currencies, such as IMF Special Drawing Rights, -1 is returned.
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
     * @param Currency|string $currency A currency instance or currency code.
     *
     * @return bool
     */
    public function is($currency)
    {
        return $this->currencyCode === (string) $currency;
    }

    /**
     * Returns the ISO 4217 currency code of this currency.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->currencyCode;
    }
}
