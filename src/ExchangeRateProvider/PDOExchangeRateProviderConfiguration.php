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
    public $sourceCurrency;

    /**
     * Whether the source currency is fixed. Optional, defaults to false.
     *
     * When false, the source currency is dynamic and $sourceCurrency must contain the name of the database column.
     * When true, the source currency is fixed and $sourceCurrency must contain the source currency code.
     *
     * @var bool
     */
    public $sourceCurrencyFixed = false;

    /**
     * The name of the column that holds the target currency code. Required.
     *
     * @var string
     */
    public $targetCurrency;

    /**
     * Whether the target currency is fixed. Optional, defaults to false.
     *
     * When false, the target currency is dynamic and $targetCurrency must contain the name of the database column.
     * When true, the target currency is fixed and $targetCurrency must contain the target currency code.
     *
     * @var bool
     */
    public $targetCurrencyFixed = false;

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
