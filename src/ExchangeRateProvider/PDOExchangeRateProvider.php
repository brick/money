<?php

namespace Brick\Money\ExchangeRateProvider;

use Brick\Money\ExchangeRateProvider;
use Brick\Money\Exception\CurrencyConversionException;

/**
 * Reads exchange rates from a PDO database connection.
 */
class PDOExchangeRateProvider implements ExchangeRateProvider
{
    /**
     * The SELECT statement.
     *
     * @var \PDOStatement
     */
    private $statement;

    /**
     * The source currency code if fixed, or null if dynamic.
     *
     * @var string|null
     */
    private $sourceCurrency;

    /**
     * The target currency code if fixed, or null if dynamic.
     *
     * @var string|null
     */
    private $targetCurrency;

    /**
     * Extra parameters set dynamically to resolve the query placeholders.
     *
     * @var array
     */
    private $parameters = [];

    /**
     * @param \PDO                                 $pdo
     * @param PDOExchangeRateProviderConfiguration $configuration
     */
    public function __construct(\PDO $pdo, PDOExchangeRateProviderConfiguration $configuration)
    {
        $conditions = [];

        if ($configuration->whereConditions !== null) {
            $conditions[] = '(' . $configuration->whereConditions . ')';
        }

        if ($configuration->sourceCurrencyFixed) {
            $this->sourceCurrency = $configuration->sourceCurrency;
        } else {
            $conditions[] = $configuration->sourceCurrency . ' = ?';
        }

        if ($configuration->targetCurrencyFixed) {
            $this->targetCurrency = $configuration->targetCurrency;
        } else {
            $conditions[] = $configuration->targetCurrency . ' = ?';
        }

        $conditions = implode(' AND ' , $conditions);

        $query = sprintf(
            'SELECT %s FROM %s WHERE %s',
            $configuration->exchangeRateColumnName,
            $configuration->tableName,
            $conditions
        );

        $this->statement = $pdo->prepare($query);
    }

    /**
     * Sets the parameters to dynamically resolve the extra query placeholders, if any.
     *
     * This is used in conjunction with $whereConditions in the configuration class.
     * The number of parameters passed to this method must match the number of placeholders.
     *
     * @param mixed ...$parameters
     *
     * @return void
     */
    public function setParameters(...$parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate($sourceCurrencyCode, $targetCurrencyCode)
    {
        $parameters = $this->parameters;

        if ($this->sourceCurrency === null) {
            $parameters[] = $sourceCurrencyCode;
        } elseif ($this->sourceCurrency !== $sourceCurrencyCode) {
            throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode);
        }

        if ($this->targetCurrency === null) {
            $parameters[] = $targetCurrencyCode;
        } elseif ($this->targetCurrency !== $targetCurrencyCode) {
            throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode);
        }

        $this->statement->execute($parameters);

        $exchangeRate = $this->statement->fetchColumn();

        if ($exchangeRate === false) {
            throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode);
        }

        return $exchangeRate;
    }
}
