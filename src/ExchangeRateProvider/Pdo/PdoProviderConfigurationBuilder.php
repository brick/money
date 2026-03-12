<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider\Pdo;

use Brick\Money\Exception\InvalidArgumentException;
use Closure;

use function implode;
use function sprintf;

final class PdoProviderConfigurationBuilder
{
    private ?string $sourceCurrencyCode = null;

    private ?string $sourceCurrencyColumnName = null;

    private ?string $targetCurrencyCode = null;

    private ?string $targetCurrencyColumnName = null;

    private ?SqlCondition $staticCondition = null;

    /**
     * @var array<string, Closure(mixed): (SqlCondition|null)>
     */
    private array $dimensionBindings = [];

    /**
     * @var list<string>
     */
    private array $orderByClauses = [];

    /**
     * The PDO provider builder accepts trusted SQL identifiers and SQL fragments. Table names, column names, ORDER BY
     * columns, and any SQL contributed via SqlCondition are interpolated directly into the generated query.
     *
     * Only parameter values are bound through PDO placeholders. Callers must ensure that all identifier and SQL
     * inputs passed to this builder are safe and not derived from untrusted input.
     *
     * @param string $tableName              The table name containing exchange rates.
     * @param string $exchangeRateColumnName The column containing the exchange rate value.
     */
    public function __construct(
        private readonly string $tableName,
        private readonly string $exchangeRateColumnName,
    ) {
    }

    /**
     * Sets a fixed source currency code.
     *
     * Mutually exclusive with `setSourceCurrencyColumn()`: only one source selector can be configured.
     */
    public function setFixedSourceCurrency(string $sourceCurrencyCode): self
    {
        if ($this->sourceCurrencyCode !== null) {
            throw new InvalidArgumentException('Source currency selector already set to fixed source currency.');
        }

        if ($this->sourceCurrencyColumnName !== null) {
            throw new InvalidArgumentException('Source currency selector already set to source currency column.');
        }

        $this->sourceCurrencyCode = $sourceCurrencyCode;

        return $this;
    }

    /**
     * Sets the source currency code column name.
     *
     * Mutually exclusive with `setFixedSourceCurrency()`: only one source selector can be configured.
     * The column name is interpolated directly into SQL and must therefore be trusted.
     */
    public function setSourceCurrencyColumn(string $sourceCurrencyColumnName): self
    {
        if ($this->sourceCurrencyCode !== null) {
            throw new InvalidArgumentException('Source currency selector already set to fixed source currency.');
        }

        if ($this->sourceCurrencyColumnName !== null) {
            throw new InvalidArgumentException('Source currency selector already set to source currency column.');
        }

        $this->sourceCurrencyColumnName = $sourceCurrencyColumnName;

        return $this;
    }

    /**
     * Sets a fixed target currency code.
     *
     * Mutually exclusive with `setTargetCurrencyColumn()`: only one target selector can be configured.
     */
    public function setFixedTargetCurrency(string $targetCurrencyCode): self
    {
        if ($this->targetCurrencyCode !== null) {
            throw new InvalidArgumentException('Target currency selector already set to fixed target currency.');
        }

        if ($this->targetCurrencyColumnName !== null) {
            throw new InvalidArgumentException('Target currency selector already set to target currency column.');
        }

        $this->targetCurrencyCode = $targetCurrencyCode;

        return $this;
    }

    /**
     * Sets the target currency code column name.
     *
     * Mutually exclusive with `setFixedTargetCurrency()`: only one target selector can be configured.
     * The column name is interpolated directly into SQL and must therefore be trusted.
     */
    public function setTargetCurrencyColumn(string $targetCurrencyColumnName): self
    {
        if ($this->targetCurrencyCode !== null) {
            throw new InvalidArgumentException('Target currency selector already set to fixed target currency.');
        }

        if ($this->targetCurrencyColumnName !== null) {
            throw new InvalidArgumentException('Target currency selector already set to target currency column.');
        }

        $this->targetCurrencyColumnName = $targetCurrencyColumnName;

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
     * When getExchangeRate() receives this dimension key, the callback may:
     * - return a SqlCondition to append to the WHERE clause, or
     * - return null to contribute no condition for this dimension value.
     *
     * Any SQL fragment returned via SqlCondition is interpolated directly into SQL and must therefore be trusted. Only
     * the SqlCondition parameter values are bound safely through PDO placeholders.
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
        if ($this->orderByClauses !== []) {
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
        if ($this->orderByClauses === []) {
            throw new InvalidArgumentException('Order by is not set. Call orderBy() first.');
        }

        return $this->addOrderBy($column, $direction);
    }

    /**
     * Builds the final PdoProviderConfiguration.
     */
    public function build(): PdoProviderConfiguration
    {
        return PdoProviderConfiguration::fromBuilder($this);
    }

    /**
     * Validates the builder state.
     */
    public function validate(): void
    {
        if ($this->sourceCurrencyCode === null && $this->sourceCurrencyColumnName === null) {
            throw new InvalidArgumentException(
                'A source currency selector must be configured using setFixedSourceCurrency() or setSourceCurrencyColumn().',
            );
        }

        if ($this->targetCurrencyCode === null && $this->targetCurrencyColumnName === null) {
            throw new InvalidArgumentException(
                'A target currency selector must be configured using setFixedTargetCurrency() or setTargetCurrencyColumn().',
            );
        }
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getExchangeRateColumnName(): string
    {
        return $this->exchangeRateColumnName;
    }

    public function getSourceCurrencyCode(): ?string
    {
        return $this->sourceCurrencyCode;
    }

    public function getSourceCurrencyColumnName(): ?string
    {
        return $this->sourceCurrencyColumnName;
    }

    public function getTargetCurrencyCode(): ?string
    {
        return $this->targetCurrencyCode;
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
        if ($this->orderByClauses === []) {
            return null;
        }

        return implode(', ', $this->orderByClauses);
    }

    private function addOrderBy(string $column, string $direction): self
    {
        if ($direction !== 'ASC' && $direction !== 'DESC') {
            throw new InvalidArgumentException('Order direction must be ASC or DESC.');
        }

        $this->orderByClauses[] = $column . ' ' . $direction;

        return $this;
    }
}
