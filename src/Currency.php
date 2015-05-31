<?php

namespace Brick\Money;

/**
 * A currency as defined by ISO 4217.
 */
class Currency
{
    /**
     * @var array|null
     */
    private static $currencies = null;

    /**
     * @var array
     */
    private static $instances = [];

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
     * The UTF-8 currency symbol.
     *
     * @var string
     */
    private $symbol;

    /**
     * The default number of fraction digits used with this currency.
     *
     * @var int
     */
    private $defaultFractionDigits;

    /**
     * Private constructor. Use getInstance() to obtain an instance.
     *
     * @param string  $currencyCode  The ISO 4217 alphabetic currency code.
     * @param integer $numericCode   The ISO 4217 numeric currency code.
     * @param string  $name          The English currency name.
     * @param string  $symbol        The UTF-8 currency symbol.
     * @param integer $decimalPlaces The default number of fraction digits.
     */
    private function __construct($currencyCode, $numericCode, $name, $symbol, $decimalPlaces)
    {
        $this->currencyCode          = $currencyCode;
        $this->numericCode           = $numericCode;
        $this->name                  = $name;
        $this->symbol                = $symbol;
        $this->defaultFractionDigits = $decimalPlaces;
    }

    /**
     * @return void
     */
    private static function loadCurrencyData()
    {
        if (self::$currencies === null) {
            self::$currencies = require __DIR__ . '/../data/currencies.php';
        }
    }

    /**
     * Returns the Currency instance for the given currency code.
     *
     * @param Currency|string $currency
     *
     * @return Currency
     *
     * @throws \InvalidArgumentException
     */
    public static function of($currency)
    {
        if ($currency instanceof Currency) {
            return $currency;
        }

        $currency = (string) $currency;

        if (! isset(self::$instances[$currency])) {
            self::loadCurrencyData();

            if (! isset(self::$currencies[$currency])) {
                throw new \InvalidArgumentException('Invalid currency code: ' . $currency);
            }

            list ($currencyCode, $numericCode, $name, $symbol, $fractionDigits) = self::$currencies[$currency];

            self::$instances[$currency] = new self($currencyCode, $numericCode, $name, $symbol, $fractionDigits);
        }

        return self::$instances[$currency];
    }

    /**
     * Returns all the available currencies.
     *
     * @return Currency[]
     */
    public static function getAvailableCurrencies()
    {
        self::loadCurrencyData();

        $currencies = [];

        foreach (array_keys(self::$currencies) as $currencyCode) {
            $currencies[] = self::of($currencyCode);
        }

        return $currencies;
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
     * @return string
     */
    public function getSymbol()
    {
        return $this->symbol;
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
     * @param Currency $currency
     *
     * @return bool
     */
    public function isEqualTo(Currency $currency)
    {
        return $this->currencyCode === $currency->currencyCode;
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
