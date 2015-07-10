<?php

namespace Brick\Money\CurrencyConversion\ExchangeRateProvider;

/**
 * Configuration for the PDOProvider.
 */
class PDOExchangeRateProviderConfiguration
{
    /**
     * The name of the table that holds the exchange rates.
     *
     * @var string
     */
    public $tableName;

    /**
     * The name of the column that holds the source currency code.
     *
     * @var string
     */
    public $sourceCurrencyColumnName;

    /**
     * The name of the column that holds the target currency code.
     *
     * @var string
     */
    public $targetCurrencyColumnName;

    /**
     * The name of the column that holds the exchange rate for the currency pair.
     *
     * @var string
     */
    public $exchangeRateColumnName;
}
