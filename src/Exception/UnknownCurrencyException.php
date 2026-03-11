<?php

declare(strict_types=1);

namespace Brick\Money\Exception;

use RuntimeException;

use function implode;
use function sprintf;

/**
 * Exception thrown when attempting to create a Currency from an unknown currency code.
 */
final class UnknownCurrencyException extends RuntimeException implements MoneyException
{
    /**
     * @internal
     *
     * @pure
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function unknownCurrency(string|int $currencyCode): self
    {
        return new self('Unknown currency code: ' . $currencyCode . '.');
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function noCurrencyForCountry(string $countryCode): self
    {
        return new self('No currency found for country ' . $countryCode . '.');
    }

    /**
     * @internal
     *
     * @param string[] $currencyCodes
     *
     * @pure
     */
    public static function severalCurrenciesForCountry(string $countryCode, array $currencyCodes): self
    {
        return new self(sprintf(
            'Several currencies found for country %s: %s.',
            $countryCode,
            implode(', ', $currencyCodes),
        ));
    }
}
