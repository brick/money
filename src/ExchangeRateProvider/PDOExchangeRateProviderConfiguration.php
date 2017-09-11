<?php

namespace Brick\Money\ExchangeRateProvider;

/**
 * Configuration for the PDOExchangeRateProvider.
 */
class PDOExchangeRateProviderConfiguration
{
    /**
     * The name of the table that holds the exchange rates. Required.
     *
     * @var string
     */
    public $tableName;

    /**
     * The name of the column that holds the source currency code. Required.
     *
     * @var string
     */
    public $sourceCurrencyColumnName;

    /**
     * The name of the column that holds the target currency code. Required.
     *
     * @var string
     */
    public $targetCurrencyColumnName;

    /**
     * The name of the column that holds the exchange rate for the currency pair. Required.
     *
     * @var string
     */
    public $exchangeRateColumnName;

    /**
     * Extra WHERE conditions that will be included in the database query. Optional.
     *
     * The conditions can include question mark placeholders, that will be resolved dynamically when loading
     * exchange rates. The parameters need to be set using the setParameters() method. The number of parameters
     * provided to setParameters() must match the number of placeholders.
     *
     * This can be used, for example, to query an exchange rate for a particular date.
     *
     * @var string|null
     */
    public $whereConditions;
}
