<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Money\Currency;
use Brick\Money\Exception\ExchangeRateProviderException;
use Brick\Money\ExchangeRateProvider;
use Closure;
use Override;
use PDO;
use PDOException;
use PDOStatement;

use function assert;
use function implode;
use function is_float;
use function sprintf;

/**
 * Reads exchange rates from a PDO database connection.
 */
final class PdoProvider implements ExchangeRateProvider
{
    /**
     * The PDO connection.
     */
    private readonly PDO $pdo;

    /**
     * The SELECT statement.
     */
    private readonly PDOStatement $statement;

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
     *
     * @phpstan-ignore missingType.iterableValue
     */
    private array $parameters = [];

    /**
     * @throws PDOException
     */
    public function __construct(PDO $pdo, PdoProviderConfiguration $configuration)
    {
        $this->pdo = $pdo;

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

        $conditions = implode(' AND ', $conditions);

        $query = sprintf(
            'SELECT %s FROM %s WHERE %s',
            $configuration->exchangeRateColumnName,
            $configuration->tableName,
            $conditions,
        );

        $statement = $this->exec(fn () => $pdo->prepare($query));
        assert($statement !== false);

        $this->statement = $statement;
    }

    /**
     * Sets the parameters to dynamically resolve the extra query placeholders, if any.
     *
     * This is used in conjunction with $whereConditions in the configuration class.
     * The number of parameters passed to this method must match the number of placeholders.
     */
    public function setParameters(mixed ...$parameters): void
    {
        $this->parameters = $parameters;
    }

    #[Override]
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency): ?BigNumber
    {
        $sourceCurrencyCode = $sourceCurrency->getCurrencyCode();
        $targetCurrencyCode = $targetCurrency->getCurrencyCode();

        $parameters = $this->parameters;

        if ($this->sourceCurrencyCode === null) {
            $parameters[] = $sourceCurrencyCode;
        } elseif ($this->sourceCurrencyCode !== $sourceCurrencyCode) {
            return null;
        }

        if ($this->targetCurrencyCode === null) {
            $parameters[] = $targetCurrencyCode;
        } elseif ($this->targetCurrencyCode !== $targetCurrencyCode) {
            return null;
        }

        try {
            /** @var int|float|numeric-string|false $exchangeRate */
            $exchangeRate = $this->exec(function () use ($parameters) {
                $this->statement->execute($parameters);

                return $this->statement->fetchColumn();
            });
        } catch (PDOException $e) {
            throw new ExchangeRateProviderException('Could not retrieve the exchange rate due to a PDO exception.', $e);
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
            throw new ExchangeRateProviderException('The database returned an invalid exchange rate.', $e);
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
