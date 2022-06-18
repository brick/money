<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

/**
 * Configuration for the PDOExchangeRateProvider.
 *
 * @psalm-suppress MissingConstructor
 */
final class PDOProviderConfiguration
{
    /**
     * The name of the table that holds the exchange rates. Required.
     */
    public ?string $tableName = null;

    /**
     * The name of the column that holds the source currency code. Optional.
     *
     * If not set, $sourceCurrencyCode must be set.
     */
    public ?string $sourceCurrencyColumnName = null;

    /**
     * The source currency code, if it is fixed. Optional.
     *
     * If not set, $sourceCurrencyColumnName must be set.
     */
    public ?string $sourceCurrencyCode = null;

    /**
     * The name of the column that holds the target currency code. Optional.
     *
     * If not set, $targetCurrencyCode must be set.
     */
    public ?string $targetCurrencyColumnName = null;

    /**
     * The target currency code, if it is fixed. Optional.
     *
     * If not set, $targetCurrencyColumnName must be set.
     */
    public ?string $targetCurrencyCode = null;

    /**
     * The name of the column that holds the exchange rate for the currency pair. Required.
     */
    public ?string $exchangeRateColumnName = null;

    /**
     * Extra WHERE conditions that will be included in the database query. Optional.
     *
     * The conditions can include question mark placeholders, that will be resolved dynamically when loading
     * exchange rates. The parameters need to be set using the setParameters() method. The number of parameters
     * provided to setParameters() must match the number of placeholders.
     *
     * This can be used, for example, to query an exchange rate for a particular date.
     */
    public ?string $whereConditions = null;
}
