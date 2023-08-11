<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigNumber;
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
    private readonly \PDOStatement $statement;

    /**
     * The source currency code if fixed, or null if dynamic.
     */
    private readonly ?string $sourceCurrencyCode;

    /**
     * The target currency code if fixed, or null if dynamic.
     */
    private readonly ?string $targetCurrencyCode;

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

        if ($configuration->whereConditions !== null) {
            $conditions[] = sprintf('(%s)', $configuration->whereConditions);
        }

        $sourceCurrencyCode = null;
        $targetCurrencyCode = null;

        if ($configuration->sourceCurrencyCode !== null) {
            $sourceCurrencyCode = $configuration->sourceCurrencyCode;
        } elseif ($configuration->sourceCurrencyColumnName !== null) {
            $conditions[] = sprintf('%s = ?', $configuration->sourceCurrencyColumnName);
        }

        if ($configuration->targetCurrencyCode !== null) {
            $targetCurrencyCode = $configuration->targetCurrencyCode;
        } elseif ($configuration->targetCurrencyColumnName !== null) {
            $conditions[] = sprintf('%s = ?', $configuration->targetCurrencyColumnName);
        }

        $this->sourceCurrencyCode = $sourceCurrencyCode;
        $this->targetCurrencyCode = $targetCurrencyCode;

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
     */
    public function setParameters(mixed ...$parameters) : void
    {
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode): int|float|string
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
