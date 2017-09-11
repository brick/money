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
    private $sourceCurrencyCode;

    /**
     * The target currency code if fixed, or null if dynamic.
     *
     * @var string|null
     */
    private $targetCurrencyCode;

    /**
     * Extra parameters set dynamically to resolve the query placeholders.
     *
     * @var array
     */
    private $parameters = [];

    /**
     * @param \PDO                                 $pdo
     * @param PDOExchangeRateProviderConfiguration $configuration
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(\PDO $pdo, PDOExchangeRateProviderConfiguration $configuration)
    {
        $conditions = [];

        if ($configuration->whereConditions !== null) {
            $conditions[] = '(' . $configuration->whereConditions . ')';
        }

        if ($configuration->sourceCurrencyCode !== null) {
            $this->sourceCurrencyCode = $configuration->sourceCurrencyCode;
        } elseif ($configuration->sourceCurrencyColumnName !== null) {
            $conditions[] = $configuration->sourceCurrencyColumnName . ' = ?';
        } else {
            throw new \InvalidArgumentException('Invalid configuration: one of $sourceCurrencyCode or $sourceCurrencyColumnName must be provided.');
        }

        if ($configuration->targetCurrencyCode !== null) {
            $this->targetCurrencyCode = $configuration->targetCurrencyCode;
        } elseif ($configuration->targetCurrencyColumnName !== null) {
            $conditions[] = $configuration->targetCurrencyColumnName . ' = ?';
        } else {
            throw new \InvalidArgumentException('Invalid configuration: one of $targetCurrencyCode or $targetCurrencyColumnName must be provided.');
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

        if ($this->sourceCurrencyCode === null) {
            $parameters[] = $sourceCurrencyCode;
        } elseif ($this->sourceCurrencyCode !== $sourceCurrencyCode) {
            throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode);
        }

        if ($this->targetCurrencyCode === null) {
            $parameters[] = $targetCurrencyCode;
        } elseif ($this->targetCurrencyCode !== $targetCurrencyCode) {
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
