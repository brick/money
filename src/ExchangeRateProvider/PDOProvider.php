<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Money\ExchangeRateProvider;
use Brick\Money\Exception\CurrencyConversionException;

/**
 * Reads exchange rates from a PDO database connection.
 */
final class PDOProvider implements ExchangeRateProvider
{
    /**
     * The SELECT statement.
     */
    private \PDOStatement $statement;

    /**
     * The source currency code if fixed, or null if dynamic.
     */
    private ?string $sourceCurrencyCode = null;

    /**
     * The target currency code if fixed, or null if dynamic.
     */
    private ?string $targetCurrencyCode = null;

    /**
     * Extra parameters set dynamically to resolve the query placeholders.
     */
    private array $parameters = [];

    /**
     * @param \PDO                     $pdo
     * @param PDOProviderConfiguration $configuration
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(\PDO $pdo, PDOProviderConfiguration $configuration)
    {
        $conditions = [];

        if ($configuration->tableName === null) {
            throw new \InvalidArgumentException('Invalid configuration: $tableName is not set.');
        }

        if ($configuration->exchangeRateColumnName === null) {
            throw new \InvalidArgumentException('Invalid configuration: $exchangeRateColumnName is not set.');
        }

        if ($configuration->sourceCurrencyCode !== null && $configuration->targetCurrencyCode !== null) {
            throw new \InvalidArgumentException('Invalid configuration: $sourceCurrencyCode and $targetCurrencyCode cannot be both set.');
        }

        if ($configuration->whereConditions !== null) {
            $conditions[] = '(' . $configuration->whereConditions . ')';
        }

        if ($configuration->sourceCurrencyCode !== null) {
            $this->sourceCurrencyCode = $configuration->sourceCurrencyCode;
        } elseif ($configuration->sourceCurrencyColumnName !== null) {
            $conditions[] = $configuration->sourceCurrencyColumnName . ' = ?';
        } else {
            throw new \InvalidArgumentException('Invalid configuration: one of $sourceCurrencyCode or $sourceCurrencyColumnName must be set.');
        }

        if ($configuration->targetCurrencyCode !== null) {
            $this->targetCurrencyCode = $configuration->targetCurrencyCode;
        } elseif ($configuration->targetCurrencyColumnName !== null) {
            $conditions[] = $configuration->targetCurrencyColumnName . ' = ?';
        } else {
            throw new \InvalidArgumentException('Invalid configuration: one of $targetCurrencyCode or $targetCurrencyColumnName must be set.');
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
    public function setParameters(...$parameters) : void
    {
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode)
    {
        $parameters = $this->parameters;

        if ($this->sourceCurrencyCode === null) {
            $parameters[] = $sourceCurrencyCode;
        } elseif ($this->sourceCurrencyCode !== $sourceCurrencyCode) {
            $info = 'source currency must be ' . $this->sourceCurrencyCode;

            throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode, $info);
        }

        if ($this->targetCurrencyCode === null) {
            $parameters[] = $targetCurrencyCode;
        } elseif ($this->targetCurrencyCode !== $targetCurrencyCode) {
            $info = 'target currency must be ' . $this->targetCurrencyCode;

            throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode, $info);
        }

        $this->statement->execute($parameters);

        /** @var int|float|numeric-string|false $exchangeRate */
        $exchangeRate = $this->statement->fetchColumn();

        if ($exchangeRate === false) {
            if ($this->parameters !== []) {
                $info = [];
                /** @psalm-suppress MixedAssignment */
                foreach ($this->parameters as $parameter) {
                    $info[] = var_export($parameter, true);
                }
                $info = 'parameters: ' . implode(', ', $info);
            } else {
                $info = null;
            }

            throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode, $info);
        }

        return $exchangeRate;
    }
}
