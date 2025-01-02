<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\Exception\UnknownIsoCurrencyException;


/**
 * A currency. This class is immutable.
 */
class IsoCurrency implements Currency
{
    /**
     * The currency code.
     *
     * For ISO currencies this will be the 3-letter uppercase ISO 4217 currency code.
     * For non ISO currencies no constraints are defined, but the code must be unique across an application, and must
     * not conflict with ISO currency codes.
     */
    private readonly string $currencyCode;

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
    private readonly int $numericCode;

    /**
     * The name of the currency.
     *
     * For ISO currencies this will be the official English name of the currency.
     * For non ISO currencies no constraints are defined.
     */
    private readonly string $name;

    /**
     * The default number of fraction digits (typical scale) used with this currency.
     *
     * For example, the default number of fraction digits for the Euro is 2, while for the Japanese Yen it is 0.
     * This cannot be a negative number.
     */
    private readonly int $defaultFractionDigits;

    /**
     * Class constructor.
     *
     * @param string $currencyCode The currency code.
     * @param int $numericCode The numeric currency code.
     * @param string $name The currency name.
     * @param int $defaultFractionDigits The default number of fraction digits.
     *
     * @throws UnknownIsoCurrencyException
     */
    public function __construct(string $currencyCode, int $numericCode, string $name, int $defaultFractionDigits)
    {
        if ($defaultFractionDigits < 0) {
            throw new \InvalidArgumentException('The default fraction digits cannot be less than zero.');
        }

        if (!IsoCurrencyProvider::getInstance()->hasCode($currencyCode, $numericCode)) {
            throw UnknownIsoCurrencyException::unknownCurrency($currencyCode, $numericCode);
        };

        $this->currencyCode = $currencyCode;
        $this->numericCode = $numericCode;
        $this->name = $name;
        $this->defaultFractionDigits = $defaultFractionDigits;
    }

    /**
     * Returns a Currency instance matching the given ISO currency code.
     *
     * @param string|int $currencyCode The 3-letter or numeric ISO 4217 currency code.
     *
     * @throws UnknownIsoCurrencyException If an unknown currency code is given.
     */
    public static function of(string|int $currencyCode): self
    {
        return IsoCurrencyProvider::getInstance()->getByCode($currencyCode);
    }

    /**
     * Returns a Currency instance for the given ISO country code.
     *
     * @param string $countryCode The 2-letter ISO 3166-1 country code.
     *
     * @return IsoCurrency
     *
     * @throws UnknownIsoCurrencyException If the country code is unknown, or there is no single currency for the country.
     */
    public static function ofCountry(string $countryCode): IsoCurrency
    {
        return IsoCurrencyProvider::getInstance()->getByCountryCode($countryCode);
    }

    /**
     * Returns the currency code.
     *
     * For ISO currencies this will be the 3-letter uppercase ISO 4217 currency code.
     * For non ISO currencies no constraints are defined.
     *
     * @return string
     */
    public function getCode(): string
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
    public function getNumericCode(): int
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
    public function getName(): string
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
    public function getDefaultFractionDigits(): int
    {
        return $this->defaultFractionDigits;
    }

    /**
     * Returns whether this currency is equal to the given currency.
     *
     * The currencies are considered equal if their currency codes are equal.
     *
     * @param Currency $currency The Currency instance, currency code or numeric currency code.
     *
     * @return bool
     */
    public function is(Currency $currency): bool
    {
        if ($currency instanceof IsoCurrency) {
            return $this->currencyCode === $currency->currencyCode;
        }

        return false;
    }

    final public function jsonSerialize(): string
    {
        return $this->currencyCode;
    }

    /**
     * Returns the currency code.
     */
    public function __toString(): string
    {
        return $this->currencyCode;
    }
}
