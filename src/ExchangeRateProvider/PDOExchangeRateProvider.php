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
        $query = sprintf(
            'SELECT %s FROM %s WHERE %s = ? AND %s = ?',
            $configuration->exchangeRateColumnName,
            $configuration->tableName,
            $configuration->sourceCurrencyColumnName,
            $configuration->targetCurrencyColumnName
        );

        if ($configuration->whereConditions !== null) {
            $query .= ' AND (' . $configuration->whereConditions . ')';
        }

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
        $parameters = [
            $sourceCurrencyCode,
            $targetCurrencyCode
        ];

        $parameters = array_merge($parameters, $this->parameters);
        $this->statement->execute($parameters);

        $exchangeRate = $this->statement->fetchColumn();

        if ($exchangeRate === false) {
            throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode);
        }

        return $exchangeRate;
    }
}
