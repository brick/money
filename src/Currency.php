<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\Exception\UnknownCurrencyException;
use InvalidArgumentException;
use JsonSerializable;
use Override;
use Stringable;

use function trigger_deprecation;

/**
 * A currency. This class is immutable.
 */
final readonly class Currency implements Stringable, JsonSerializable
{
    /**
     * @param string       $currencyCode          The currency code. For ISO currencies this will be the 3-letter
     *                                            uppercase ISO 4217 currency code. For non-ISO currencies no
     *                                            constraints are defined, but the code must be unique across an
     *                                            application and must not conflict with ISO currency codes.
     * @param int          $numericCode           The numeric currency code. For ISO currencies this will be the
     *                                            ISO 4217 numeric currency code, without leading zeros. For non-ISO
     *                                            currencies no constraints are defined, but the code must be unique
     *                                            across an application and must not conflict with ISO currency codes.
     *                                            Set to zero if the currency does not have a numeric code.
     * @param string       $name                  The currency name. For ISO currencies this will be the official
     *                                            English name of the currency. For non-ISO currencies no constraints
     *                                            are defined.
     * @param int          $defaultFractionDigits The default number of fraction digits (typical scale) used with this
     *                                            currency. For example, the default number of fraction digits for the
     *                                            Euro is 2, while for the Japanese Yen it is 0. This cannot be a
     *                                            negative number.
     * @param CurrencyType $currencyType          The type of the currency. For ISO currencies, this indicates whether
     *                                            the currency is currently in use (IsoCurrent) or has been withdrawn
     *                                            (IsoHistorical). For non-ISO currencies defined by the application,
     *                                            the type is Custom.
     */
    public function __construct(
        private string $currencyCode,
        private int $numericCode,
        private string $name,
        private int $defaultFractionDigits,
        private CurrencyType $currencyType = CurrencyType::Custom,
    ) {
        if ($defaultFractionDigits < 0) {
            throw new InvalidArgumentException('The default fraction digits cannot be less than zero.');
        }
    }

    /**
     * Returns a Currency instance matching the given ISO currency code.
     *
     * @param string $currencyCode The 3-letter ISO 4217 currency code.
     *
     * @throws UnknownCurrencyException If an unknown currency code is given.
     */
    public static function of(string $currencyCode): Currency
    {
        return ISOCurrencyProvider::getInstance()->getCurrency($currencyCode);
    }

    /**
     * Returns the current currency for the given ISO country code.
     *
     * Note: This value may change in minor releases, as countries may change their official currency.
     *
     * @param string $countryCode The 2-letter ISO 3166-1 country code.
     *
     * @throws UnknownCurrencyException If the country code is unknown, or there is no single currency for the country.
     */
    public static function ofCountry(string $countryCode): Currency
    {
        return ISOCurrencyProvider::getInstance()->getCurrencyForCountry($countryCode);
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
     * @throws UnknownCurrencyException If an unknown currency code is given.
     */
    public static function ofNumericCode(int $currencyCode): Currency
    {
        return ISOCurrencyProvider::getInstance()->getCurrencyByNumericCode($currencyCode);
    }

    /**
     * Returns the currency code.
     *
     * For ISO currencies this will be the 3-letter uppercase ISO 4217 currency code.
     * For non ISO currencies no constraints are defined.
     */
    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    /**
     * Returns the numeric currency code.
     *
     * For ISO currencies this will be the ISO 4217 numeric currency code, without leading zeros.
     * For non ISO currencies no constraints are defined.
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
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the default number of fraction digits (typical scale) used with this currency.
     *
     * For example, the default number of fraction digits for the Euro is 2, while for the Japanese Yen it is 0.
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
     * @deprecated Use isEqualTo() instead. Note that isEqualTo() does not support—and ignores—numeric codes.
     *
     * @param Currency|string|int $currency The Currency instance, currency code or numeric currency code.
     */
    public function is(Currency|string|int $currency): bool
    {
        trigger_deprecation('brick/money', '0.11.0', 'Calling "%s()" is deprecated, use isEqualTo() instead.', __METHOD__);

        if ($currency instanceof Currency) {
            return $this->currencyCode === $currency->currencyCode;
        }

        return $this->currencyCode === (string) $currency
            || ($this->numericCode !== 0 && $this->numericCode === (int) $currency);
    }

    /**
     * Returns whether this currency is equal to the given currency.
     *
     * The currencies are considered equal if and only if their alphabetic currency codes are equal.
     * Two currencies with the same numeric code but different alphabetic codes are NOT considered equal,
     * because numeric codes may outlive a particular currency and be reused across currency changes.
     *
     * @param Currency|string $currency The Currency instance or ISO currency code.
     */
    public function isEqualTo(Currency|string $currency): bool
    {
        $currencyCode = $currency instanceof Currency ? $currency->getCurrencyCode() : $currency;

        return $currencyCode === $this->currencyCode;
    }

    /**
     * Returns the type of this currency.
     *
     * For ISO currencies, this will be either IsoCurrent (in use) or IsoHistorical (withdrawn).
     * For application-defined currencies, the type is Custom.
     */
    public function getCurrencyType(): CurrencyType
    {
        return $this->currencyType;
    }

    #[Override]
    final public function jsonSerialize(): string
    {
        return $this->currencyCode;
    }

    /**
     * Returns the currency code.
     */
    #[Override]
    public function __toString(): string
    {
        return $this->currencyCode;
    }
}
