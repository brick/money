<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Money\Currency;
use Brick\Money\Exception\ExchangeRateProviderException;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\Pdo\PdoProviderBuilder;
use Brick\Money\ExchangeRateProvider\Pdo\SqlCondition;
use Closure;
use Override;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

use function array_diff_key;
use function array_keys;
use function assert;
use function get_debug_type;
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
     * The cached prepared statements indexed by SQL query.
     *
     * @var array<string, PDOStatement>
     */
    private array $statements = [];

    /**
     * @param array<string, Closure(mixed): (SqlCondition|null)> $dimensionBindings
     */
    private function __construct(
        private readonly PDO $pdo,
        private readonly string $tableName,
        private readonly string $exchangeRateColumnName,
        private readonly string|int|null $sourceCurrencyCode,
        private readonly ?string $sourceCurrencyColumnName,
        private readonly string|int|null $targetCurrencyCode,
        private readonly ?string $targetCurrencyColumnName,
        private readonly bool $useNumericCurrencyCode,
        private readonly ?SqlCondition $staticCondition,
        private array $dimensionBindings,
        private readonly ?string $orderBy,
    ) {
    }

    /**
     * @param PDO    $pdo                    The PDO instance.
     * @param string $tableName              The table name containing exchange rates.
     * @param string $exchangeRateColumnName The column containing the exchange rate value.
     */
    public static function builder(PDO $pdo, string $tableName, string $exchangeRateColumnName): PdoProviderBuilder
    {
        return new PdoProviderBuilder($pdo, $tableName, $exchangeRateColumnName);
    }

    /**
     * @internal
     */
    public static function fromBuilder(PdoProviderBuilder $builder): self
    {
        $builder->validate();

        return new self(
            pdo: $builder->getPdo(),
            tableName: $builder->getTableName(),
            exchangeRateColumnName: $builder->getExchangeRateColumnName(),
            sourceCurrencyCode: $builder->getSourceCurrencyCode(),
            sourceCurrencyColumnName: $builder->getSourceCurrencyColumnName(),
            targetCurrencyCode: $builder->getTargetCurrencyCode(),
            targetCurrencyColumnName: $builder->getTargetCurrencyColumnName(),
            useNumericCurrencyCode: $builder->getUseNumericCurrencyCode(),
            staticCondition: $builder->getStaticCondition(),
            dimensionBindings: $builder->getDimensionBindings(),
            orderBy: $builder->getOrderBy(),
        );
    }

    #[Override]
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber
    {
        if ($sourceCurrency->isEqualTo($targetCurrency)) {
            return BigInteger::one();
        }

        $sourceCurrencyCode = $this->useNumericCurrencyCode
            ? $sourceCurrency->getNumericCode()
            : $sourceCurrency->getCurrencyCode();
        $targetCurrencyCode = $this->useNumericCurrencyCode
            ? $targetCurrency->getNumericCode()
            : $targetCurrency->getCurrencyCode();

        if ($sourceCurrencyCode === null || $targetCurrencyCode === null) {
            return null;
        }

        $conditions = [];
        $parameters = [];

        if ($this->staticCondition !== null) {
            $conditions[] = sprintf('(%s)', $this->staticCondition->getSql());
            $parameters = $this->staticCondition->getParameters();
        }

        if ($this->sourceCurrencyCode === null) {
            $conditions[] = sprintf('%s = ?', $this->sourceCurrencyColumnName);
            $parameters[] = $sourceCurrencyCode;
        } elseif ($this->sourceCurrencyCode !== $sourceCurrencyCode) {
            return null;
        }

        if ($this->targetCurrencyCode === null) {
            $conditions[] = sprintf('%s = ?', $this->targetCurrencyColumnName);
            $parameters[] = $targetCurrencyCode;
        } elseif ($this->targetCurrencyCode !== $targetCurrencyCode) {
            return null;
        }

        ksort($dimensions);
        $omittedDimensions = array_keys(array_diff_key($this->dimensionBindings, $dimensions));

        foreach ($dimensions as $dimension => $value) {
            if (! isset($this->dimensionBindings[$dimension])) {
                return null;
            }

            $dimensionResolver = $this->dimensionBindings[$dimension];

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

            // @phpstan-ignore instanceof.alwaysTrue
            if (! $sqlCondition instanceof SqlCondition) {
                throw new ExchangeRateProviderException(sprintf(
                    'Dimension resolver must return %s|null, but returned %s for dimension "%s".',
                    SqlCondition::class,
                    get_debug_type($sqlCondition),
                    $dimension,
                ));
            }

            $conditions[] = sprintf('(%s)', $sqlCondition->getSql());
            foreach ($sqlCondition->getParameters() as $parameter) {
                $parameters[] = $parameter;
            }
        }

        $query = sprintf(
            'SELECT %s FROM %s',
            $this->exchangeRateColumnName,
            $this->tableName,
        );

        if ($conditions !== []) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if ($this->orderBy !== null) {
            $query .= ' ORDER BY ' . $this->orderBy;
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
                try {
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
                } finally {
                    $statement->closeCursor();
                }
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
            $exchangeRate = BigNumber::of($exchangeRate);
        } catch (MathException $e) {
            throw new ExchangeRateProviderException('Database returned an invalid exchange rate value.', $e);
        }

        if ($exchangeRate->isNegativeOrZero()) {
            throw new ExchangeRateProviderException('Database returned a non-positive exchange rate value.');
        }

        return $exchangeRate;
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
