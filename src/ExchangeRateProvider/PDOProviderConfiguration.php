<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use InvalidArgumentException;

/**
 * Configuration for the PDOExchangeRateProvider.
 */
final class PDOProviderConfiguration
{
    public function __construct(
        /**
         * The name of the table that holds the exchange rates. Required.
         */
        public readonly string $tableName,

        /**
         * The name of the column that holds the exchange rate for the currency pair. Required.
         */
        public readonly string $exchangeRateColumnName,

        /**
         * The source currency code, if it is fixed. Optional.
         *
         * If not set, $sourceCurrencyColumnName must be set.
         */
        public readonly ?string $sourceCurrencyCode = null,

        /**
         * The name of the column that holds the source currency code. Optional.
         *
         * If not set, $sourceCurrencyCode must be set.
         */
        public readonly ?string $sourceCurrencyColumnName = null,

        /**
         * The target currency code, if it is fixed. Optional.
         *
         * If not set, $targetCurrencyColumnName must be set.
         */
        public readonly ?string $targetCurrencyCode = null,

        /**
         * The name of the column that holds the target currency code. Optional.
         *
         * If not set, $targetCurrencyCode must be set.
         */
        public readonly ?string $targetCurrencyColumnName = null,

        /**
         * Extra WHERE conditions that will be included in the database query. Optional.
         *
         * The conditions can include question mark placeholders, that will be resolved dynamically when loading
         * exchange rates. The parameters need to be set using the setParameters() method. The number of parameters
         * provided to setParameters() must match the number of placeholders.
         *
         * This can be used, for example, to query an exchange rate for a particular date.
         */
        public readonly ?string $whereConditions = null,
    ) {
        if ($sourceCurrencyCode === null && $sourceCurrencyColumnName === null) {
            throw new InvalidArgumentException(
                'Invalid configuration: one of $sourceCurrencyCode or $sourceCurrencyColumnName must be set.',
            );
        }

        if ($sourceCurrencyCode !== null && $sourceCurrencyColumnName !== null) {
            throw new InvalidArgumentException(
                'Invalid configuration: $sourceCurrencyCode and $sourceCurrencyColumnName cannot be both set.',
            );
        }

        if ($targetCurrencyCode === null && $targetCurrencyColumnName === null) {
            throw new \InvalidArgumentException(
                'Invalid configuration: one of $targetCurrencyCode or $targetCurrencyColumnName must be set.',
            );
        }

        if ($targetCurrencyCode !== null && $targetCurrencyColumnName !== null) {
            throw new InvalidArgumentException(
                'Invalid configuration: $targetCurrencyCode and $targetCurrencyColumnName cannot be both set.',
            );
        }

        if ($sourceCurrencyCode !== null && $targetCurrencyCode !== null) {
            throw new InvalidArgumentException(
                'Invalid configuration: $sourceCurrencyCode and $targetCurrencyCode cannot be both set.',
            );
        }
    }
}
