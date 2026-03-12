<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider\Pdo;

use Closure;

/**
 * Configuration for the PdoProvider.
 */
final readonly class PdoProviderConfiguration
{
    private function __construct(
        /**
         * The name of the table that holds the exchange rates. Required.
         */
        public string $tableName,

        /**
         * The name of the column that holds the exchange rate for the currency pair. Required.
         */
        public string $exchangeRateColumnName,

        /**
         * The source currency code, if it is fixed. Optional.
         *
         * If not set, $sourceCurrencyColumnName must be set.
         */
        public ?string $sourceCurrencyCode,

        /**
         * The name of the column that holds the source currency code. Optional.
         *
         * If not set, $sourceCurrencyCode must be set.
         */
        public ?string $sourceCurrencyColumnName,

        /**
         * The target currency code, if it is fixed. Optional.
         *
         * If not set, $targetCurrencyColumnName must be set.
         */
        public ?string $targetCurrencyCode,

        /**
         * The name of the column that holds the target currency code. Optional.
         *
         * If not set, $targetCurrencyCode must be set.
         */
        public ?string $targetCurrencyColumnName,

        /**
         * Extra WHERE conditions that will be included in the database query. Optional.
         *
         * This can be used to add extra static constraints that are always applied to the query.
         * Dynamic constraints should be modeled with dimensionBindings.
         */
        public ?SqlCondition $staticCondition,

        /**
         * Extra dimensions that can be provided to getExchangeRate() and mapped to SQL conditions.
         *
         * @var array<string, Closure(mixed): (SqlCondition|null)>
         */
        public array $dimensionBindings,

        /**
         * Optional ORDER BY clause used to deterministically pick the first row when multiple rows match.
         */
        public ?string $orderBy,
    ) {
    }

    /**
     * Creates a builder for PdoProvider configuration.
     *
     * @param string $tableName              The table name containing exchange rates.
     * @param string $exchangeRateColumnName The column containing the exchange rate value.
     */
    public static function builder(
        string $tableName,
        string $exchangeRateColumnName,
    ): PdoProviderConfigurationBuilder {
        return new PdoProviderConfigurationBuilder($tableName, $exchangeRateColumnName);
    }

    /**
     * @internal
     */
    public static function fromBuilder(PdoProviderConfigurationBuilder $builder): self
    {
        $builder->validate();

        return new self(
            tableName: $builder->getTableName(),
            exchangeRateColumnName: $builder->getExchangeRateColumnName(),
            sourceCurrencyCode: $builder->getSourceCurrencyCode(),
            sourceCurrencyColumnName: $builder->getSourceCurrencyColumnName(),
            targetCurrencyCode: $builder->getTargetCurrencyCode(),
            targetCurrencyColumnName: $builder->getTargetCurrencyColumnName(),
            staticCondition: $builder->getStaticCondition(),
            dimensionBindings: $builder->getDimensionBindings(),
            orderBy: $builder->getOrderBy(),
        );
    }
}
