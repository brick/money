<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Money\Currency;
use Brick\Money\Exception\ExchangeRateProviderException;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\Pdo\PdoProviderConfiguration;
use Closure;
use Override;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

use function array_diff_key;
use function array_keys;
use function assert;
use function implode;
use function is_float;
use function ksort;
use function sprintf;

/**
 * Reads exchange rates from a PDO database connection.
 *
 * This API is intentionally low-level: table names, column names, ORDER BY clauses, and SQL condition fragments are
 * interpolated into the generated SQL as trusted configuration. They are not quoted or escaped by this library.
 * Only parameter values are bound safely through PDO placeholders.
 *
 * Callers are responsible for ensuring that all SQL identifiers and SQL fragments supplied to the builder are safe
 * and never derived from untrusted input.
 */
final class PdoProvider implements ExchangeRateProvider
{
    /**
     * The PDO connection.
     */
    private readonly PDO $pdo;

    /**
     * The configuration object.
     */
    private readonly PdoProviderConfiguration $configuration;

    /**
     * The cached prepared statements indexed by SQL query.
     *
     * @var array<string, PDOStatement>
     */
    private array $statements = [];

    public function __construct(PDO $pdo, PdoProviderConfiguration $configuration)
    {
        $this->pdo = $pdo;
        $this->configuration = $configuration;
    }

    #[Override]
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber
    {
        $sourceCurrencyCode = $sourceCurrency->getCurrencyCode();
        $targetCurrencyCode = $targetCurrency->getCurrencyCode();

        if ($sourceCurrency->isEqualTo($targetCurrency)) {
            return BigInteger::one();
        }

        $conditions = [];
        $parameters = [];

        if ($this->configuration->staticCondition !== null) {
            $conditions[] = sprintf('(%s)', $this->configuration->staticCondition->getSql());
            $parameters = $this->configuration->staticCondition->getParameters();
        }

        if ($this->configuration->sourceCurrencyCode === null) {
            $conditions[] = sprintf('%s = ?', $this->configuration->sourceCurrencyColumnName);
            $parameters[] = $sourceCurrencyCode;
        } elseif ($this->configuration->sourceCurrencyCode !== $sourceCurrencyCode) {
            return null;
        }

        if ($this->configuration->targetCurrencyCode === null) {
            $conditions[] = sprintf('%s = ?', $this->configuration->targetCurrencyColumnName);
            $parameters[] = $targetCurrencyCode;
        } elseif ($this->configuration->targetCurrencyCode !== $targetCurrencyCode) {
            return null;
        }

        ksort($dimensions);
        $omittedDimensions = array_keys(array_diff_key($this->configuration->dimensionBindings, $dimensions));

        foreach ($dimensions as $dimension => $value) {
            if (! isset($this->configuration->dimensionBindings[$dimension])) {
                return null;
            }

            $dimensionResolver = $this->configuration->dimensionBindings[$dimension];

            try {
                $sqlCondition = $dimensionResolver($value);
            } catch (Throwable $e) {
                if ($e instanceof ExchangeRateProviderException) {
                    throw $e;
                }

                throw new ExchangeRateProviderException(sprintf(
                    'An exception occurred while resolving SQL condition for dimension: %s.',
                    $dimension,
                ), $e);
            }

            if ($sqlCondition === null) {
                continue;
            }

            $conditions[] = sprintf('(%s)', $sqlCondition->getSql());
            foreach ($sqlCondition->getParameters() as $parameter) {
                $parameters[] = $parameter;
            }
        }

        $query = sprintf(
            'SELECT %s FROM %s',
            $this->configuration->exchangeRateColumnName,
            $this->configuration->tableName,
        );

        if ($conditions !== []) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if ($this->configuration->orderBy !== null) {
            $query .= ' ORDER BY ' . $this->configuration->orderBy;
            $query .= ' LIMIT 1';
        }

        if (! isset($this->statements[$query])) {
            try {
                $statement = $this->exec(fn () => $this->pdo->prepare($query));
            } catch (PDOException $e) {
                throw new ExchangeRateProviderException('Failed to prepare exchange rate query due to a PDO exception.', $e);
            }

            assert($statement !== false);

            $this->statements[$query] = $statement;
        }

        $statement = $this->statements[$query];

        try {
            /** @var int|float|numeric-string|false $exchangeRate */
            $exchangeRate = $this->exec(function () use ($statement, $parameters, $omittedDimensions) {
                $statement->execute($parameters);

                $exchangeRate = $statement->fetchColumn();

                if ($exchangeRate === false) {
                    return false;
                }

                if ($statement->fetchColumn() !== false) {
                    $message = 'Exchange rate lookup matched multiple rows.';

                    if ($omittedDimensions !== []) {
                        $message .= ' Missing dimensions may be required to disambiguate: ' .
                            implode(', ', $omittedDimensions) . '.';
                    }

                    $message .= ' Configure orderBy() to select one row deterministically if that is intended.';

                    throw new ExchangeRateProviderException($message);
                }

                return $exchangeRate;
            });
        } catch (PDOException $e) {
            throw new ExchangeRateProviderException('Failed to retrieve exchange rate due to a PDO exception.', $e);
        }

        if ($exchangeRate === false) {
            return null;
        }

        if (is_float($exchangeRate)) {
            $exchangeRate = (string) $exchangeRate;
        }

        try {
            return BigNumber::of($exchangeRate);
        } catch (MathException $e) {
            throw new ExchangeRateProviderException('Database returned an invalid exchange rate value.', $e);
        }
    }

    /**
     * Executes a callback with PDO configured to throw exceptions, then restores the previous error mode.
     *
     * @template T
     *
     * @param Closure(): T $callback
     *
     * @return T
     *
     * @throws PDOException
     */
    private function exec(Closure $callback): mixed
    {
        /** @var int $previousErrMode */
        $previousErrMode = $this->pdo->getAttribute(PDO::ATTR_ERRMODE);

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            return $callback();
        } finally {
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, $previousErrMode);
        }
    }
}
