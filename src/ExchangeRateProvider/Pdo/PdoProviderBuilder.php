<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider\Pdo;

use Brick\Money\Exception\InvalidArgumentException;
use Brick\Money\ExchangeRateProvider\PdoProvider;
use Closure;
use PDO;

use function implode;
use function is_int;
use function is_string;
use function sprintf;

final class PdoProviderBuilder
{
    private string|int|null $sourceCurrencyCode = null;

    private ?string $sourceCurrencyColumnName = null;

    private string|int|null $targetCurrencyCode = null;

    private ?string $targetCurrencyColumnName = null;

    private bool $useNumericCurrencyCode = false;

    private ?SqlCondition $staticCondition = null;

    /**
     * @var array<string, Closure(mixed): (SqlCondition|null)>
     */
    private array $dimensionBindings = [];

    /**
     * @var list<string>
     */
    private array $orderBy = [];

    /**
     * Note: The configured table names, column names, ORDER BY clauses, and SQL condition fragments are not quoted or
     * escaped by this library. Only parameter values are bound safely through PDO placeholders.
     *
     * Callers are responsible for ensuring that all SQL identifiers and SQL fragments supplied to the builder are safe
     * and never derived from untrusted input.
     *
     * @param string $tableName              The table name containing exchange rates.
     * @param string $exchangeRateColumnName The column containing the exchange rate value.
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $tableName,
        private readonly string $exchangeRateColumnName,
    ) {
    }

    /**
     * Sets a fixed source currency code.
     *
     * Mutually exclusive with `setSourceCurrencyColumn()`: only one source selector can be configured.
     */
    public function setFixedSourceCurrencyCode(string|int $sourceCurrencyCode): self
    {
        $this->ensureSourceSelectorUnset();

        $this->sourceCurrencyCode = $sourceCurrencyCode;

        return $this;
    }

    /**
     * Sets the source currency code column name.
     *
     * Mutually exclusive with `setFixedSourceCurrencyCode()`: only one source selector can be configured.
     * The column name is interpolated directly into SQL and must therefore be trusted.
     */
    public function setSourceCurrencyColumn(string $sourceCurrencyColumnName): self
    {
        $this->ensureSourceSelectorUnset();

        $this->sourceCurrencyColumnName = $sourceCurrencyColumnName;

        return $this;
    }

    /**
     * Sets a fixed target currency code.
     *
     * Mutually exclusive with `setTargetCurrencyColumn()`: only one target selector can be configured.
     */
    public function setFixedTargetCurrencyCode(string|int $targetCurrencyCode): self
    {
        $this->ensureTargetSelectorUnset();

        $this->targetCurrencyCode = $targetCurrencyCode;

        return $this;
    }

    /**
     * Sets the target currency code column name.
     *
     * Mutually exclusive with `setFixedTargetCurrencyCode()`: only one target selector can be configured.
     * The column name is interpolated directly into SQL and must therefore be trusted.
     */
    public function setTargetCurrencyColumn(string $targetCurrencyColumnName): self
    {
        $this->ensureTargetSelectorUnset();

        $this->targetCurrencyColumnName = $targetCurrencyColumnName;

        return $this;
    }

    /**
     * Configures the provider to use numeric currency codes instead of alphabetic currency codes.
     *
     * This is intended for database schemas that store ISO 4217 numeric codes instead of alphabetic codes.
     *
     * Note: numeric codes are not the library's canonical currency identity and may be reassigned by ISO over time, so
     * this mode has weaker stability guarantees than alphabetic-code lookups. In addition, custom currencies without a
     * numeric code cannot be matched in this mode.
     */
    public function useNumericCurrencyCode(): self
    {
        if ($this->useNumericCurrencyCode) {
            throw new InvalidArgumentException('Numeric currency code mode is already enabled.');
        }

        $this->useNumericCurrencyCode = true;

        return $this;
    }

    /**
     * Sets a static SQL condition fragment that is always appended to the WHERE clause.
     *
     * The SQL fragment inside the given SqlCondition is interpolated directly into SQL and must therefore be trusted.
     */
    public function setStaticCondition(SqlCondition $staticCondition): self
    {
        if ($this->staticCondition !== null) {
            throw new InvalidArgumentException('Static condition is already set.');
        }

        $this->staticCondition = $staticCondition;

        return $this;
    }

    /**
     * Registers a lookup dimension and maps it to a callback that may contribute a SQL condition fragment.
     *
     * When getExchangeRate() receives this dimension key, the callback receives the dimension value, and may:
     * - return a SqlCondition to append to the WHERE clause, or
     * - return null to contribute no condition for this dimension value.
     *
     * The callback may throw an ExchangeRateProviderException if an error occurs.
     *
     * @param callable(mixed): (SqlCondition|null) $resolver A callback that resolves the dimension value to an optional
     *                                                       SQL condition and its positional parameters.
     */
    public function bindDimension(
        string $dimension,
        callable $resolver,
    ): self {
        if (isset($this->dimensionBindings[$dimension])) {
            throw new InvalidArgumentException(sprintf('Dimension already bound: %s.', $dimension));
        }

        $this->dimensionBindings[$dimension] = Closure::fromCallable($resolver);

        return $this;
    }

    /**
     * Sets the ORDER BY clause that will be used when several rows match the exchange rate query.
     *
     * If no ORDER BY clause is set, and the query returns more than one row, an exception will be thrown.
     * The column name is interpolated directly into SQL and must therefore be trusted.
     *
     * @param string       $column    The column name to order by.
     * @param 'ASC'|'DESC' $direction The order direction, either 'ASC' or 'DESC'.
     */
    public function orderBy(string $column, string $direction): self
    {
        if ($this->orderBy !== []) {
            throw new InvalidArgumentException('Order by is already set. Use thenOrderBy() to add more columns.');
        }

        return $this->addOrderBy($column, $direction);
    }

    /**
     * Appends an additional ORDER BY clause after `orderBy()`.
     *
     * The column name is interpolated directly into SQL and must therefore be trusted.
     *
     * @param string       $column    The column name to order by.
     * @param 'ASC'|'DESC' $direction The order direction, either 'ASC' or 'DESC'.
     */
    public function thenOrderBy(string $column, string $direction): self
    {
        if ($this->orderBy === []) {
            throw new InvalidArgumentException('Order by is not set. Call orderBy() first.');
        }

        return $this->addOrderBy($column, $direction);
    }

    /**
     * Builds the final PdoProvider.
     */
    public function build(): PdoProvider
    {
        return PdoProvider::fromBuilder($this);
    }

    /**
     * Validates the builder state.
     */
    public function validate(): void
    {
        if ($this->sourceCurrencyCode === null && $this->sourceCurrencyColumnName === null) {
            throw new InvalidArgumentException(
                'A source currency selector must be configured using setFixedSourceCurrencyCode() or setSourceCurrencyColumn().',
            );
        }

        if ($this->targetCurrencyCode === null && $this->targetCurrencyColumnName === null) {
            throw new InvalidArgumentException(
                'A target currency selector must be configured using setFixedTargetCurrencyCode() or setTargetCurrencyColumn().',
            );
        }

        if ($this->useNumericCurrencyCode) {
            if (is_string($this->sourceCurrencyCode)) {
                throw new InvalidArgumentException(
                    'Fixed source currency is configured as an alphabetic code, but numeric currency code mode is enabled.',
                );
            }

            if (is_string($this->targetCurrencyCode)) {
                throw new InvalidArgumentException(
                    'Fixed target currency is configured as an alphabetic code, but numeric currency code mode is enabled.',
                );
            }
        } else {
            if (is_int($this->sourceCurrencyCode)) {
                throw new InvalidArgumentException(
                    'Fixed source currency is configured as a numeric code, but numeric currency code mode is disabled. ' .
                    'Call useNumericCurrencyCode() to enable it.',
                );
            }

            if (is_int($this->targetCurrencyCode)) {
                throw new InvalidArgumentException(
                    'Fixed target currency is configured as a numeric code, but numeric currency code mode is disabled. ' .
                    'Call useNumericCurrencyCode() to enable it.',
                );
            }
        }
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getExchangeRateColumnName(): string
    {
        return $this->exchangeRateColumnName;
    }

    public function getSourceCurrencyCode(): string|int|null
    {
        return $this->sourceCurrencyCode;
    }

    public function getSourceCurrencyColumnName(): ?string
    {
        return $this->sourceCurrencyColumnName;
    }

    public function getTargetCurrencyCode(): string|int|null
    {
        return $this->targetCurrencyCode;
    }

    public function getUseNumericCurrencyCode(): bool
    {
        return $this->useNumericCurrencyCode;
    }

    public function getTargetCurrencyColumnName(): ?string
    {
        return $this->targetCurrencyColumnName;
    }

    public function getStaticCondition(): ?SqlCondition
    {
        return $this->staticCondition;
    }

    /**
     * @return array<string, Closure(mixed): (SqlCondition|null)>
     */
    public function getDimensionBindings(): array
    {
        return $this->dimensionBindings;
    }

    public function getOrderBy(): ?string
    {
        if ($this->orderBy === []) {
            return null;
        }

        return implode(', ', $this->orderBy);
    }

    private function addOrderBy(string $column, string $direction): self
    {
        if ($direction !== 'ASC' && $direction !== 'DESC') {
            throw new InvalidArgumentException('Order direction must be ASC or DESC.');
        }

        $this->orderBy[] = $column . ' ' . $direction;

        return $this;
    }

    private function ensureSourceSelectorUnset(): void
    {
        if ($this->sourceCurrencyCode !== null) {
            throw new InvalidArgumentException('Source currency selector already set to fixed source currency.');
        }

        if ($this->sourceCurrencyColumnName !== null) {
            throw new InvalidArgumentException('Source currency selector already set to source currency column.');
        }
    }

    private function ensureTargetSelectorUnset(): void
    {
        if ($this->targetCurrencyCode !== null) {
            throw new InvalidArgumentException('Target currency selector already set to fixed target currency.');
        }

        if ($this->targetCurrencyColumnName !== null) {
            throw new InvalidArgumentException('Target currency selector already set to target currency column.');
        }
    }
}
