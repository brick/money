<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\Exception\UnknownCurrencyException;
use Stringable;

/**
 * A currency. This class is immutable.
 */
final class Currency implements Stringable
{
    /**
     * The currency code.
     *
     * For ISO currencies this will be the 3-letter uppercase ISO 4217 currency code.
     * For non ISO currencies no constraints are defined, but the code must be unique across an application, and must
     * not conflict with ISO currency codes.
     */
    private string $currencyCode;

    /**
     * The numeric currency code.
     *
     * For ISO currencies this will be the ISO 4217 numeric currency code, without leading zeros.
     * For non ISO currencies no constraints are defined, but the code must be unique across an application, and must
     * not conflict with ISO currency codes.
     *
     * If set to zero, the currency is considered to not have a numeric code.
     *
     * The numeric code can be useful when storing monies in a database.
     */
    private int $numericCode;

    /**
     * The name of the currency.
     *
     * For ISO currencies this will be the official English name of the currency.
     * For non ISO currencies no constraints are defined.
     */
    private string $name;

    /**
     * The default number of fraction digits (typical scale) used with this currency.
     *
     * For example, the default number of fraction digits for the Euro is 2, while for the Japanese Yen it is 0.
     * This cannot be a negative number.
     */
    private int $defaultFractionDigits;

    /**
     * Class constructor.
     *
     * @param string $currencyCode          The currency code.
     * @param int    $numericCode           The numeric currency code.
     * @param string $name                  The currency name.
     * @param int    $defaultFractionDigits The default number of fraction digits.
     */
    public function __construct(string $currencyCode, int $numericCode, string $name, int $defaultFractionDigits)
    {
        if ($defaultFractionDigits < 0) {
            throw new \InvalidArgumentException('The default fraction digits cannot be less than zero.');
        }

        $this->currencyCode          = $currencyCode;
        $this->numericCode           = $numericCode;
        $this->name                  = $name;
        $this->defaultFractionDigits = $defaultFractionDigits;
    }

    /**
     * Returns a Currency instance matching the given ISO currency code.
     *
     * @param string|int $currencyCode The 3-letter or numeric ISO 4217 currency code.
     *
     * @throws UnknownCurrencyException If an unknown currency code is given.
     */
    public static function of(string|int $currencyCode) : Currency
    {
        return ISOCurrencyProvider::getInstance()->getCurrency($currencyCode);
    }

    /**
     * Returns a Currency instance for the given ISO country code.
     *
     * @param string $countryCode The 2-letter ISO 3166-1 country code.
     *
     * @return Currency
     *
     * @throws UnknownCurrencyException If the country code is unknown, or there is no single currency for the country.
     */
    public static function ofCountry(string $countryCode) : Currency
    {
        return ISOCurrencyProvider::getInstance()->getCurrencyForCountry($countryCode);
    }

    /**
     * Returns the currency code.
     *
     * For ISO currencies this will be the 3-letter uppercase ISO 4217 currency code.
     * For non ISO currencies no constraints are defined.
     *
     * @return string
     */
    public function getCurrencyCode() : string
    {
        return $this->currencyCode;
    }

    /**
     * Returns the numeric currency code.
     *
     * For ISO currencies this will be the ISO 4217 numeric currency code, without leading zeros.
     * For non ISO currencies no constraints are defined.
     *
     * @return int
     */
    public function getNumericCode() : int
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
    public function getName() : string
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
    public function getDefaultFractionDigits() : int
    {
        return $this->defaultFractionDigits;
    }

    /**
     * Returns whether this currency is equal to the given currency.
     *
     * The currencies are considered equal if their currency codes are equal.
     *
     * @param Currency|string|int $currency The Currency instance, currency code or numeric currency code.
     *
     * @return bool
     */
    public function is(Currency|string|int $currency) : bool
    {
        if ($currency instanceof Currency) {
            return $this->currencyCode === $currency->currencyCode;
        }

        return $this->currencyCode === (string) $currency
            || ($this->numericCode !== 0 && $this->numericCode === (int) $currency);
    }

    /**
     * Returns the currency code.
     */
    public function __toString() : string
    {
        return $this->currencyCode;
    }
}
