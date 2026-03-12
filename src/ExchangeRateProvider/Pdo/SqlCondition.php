<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider\Pdo;

use Brick\Money\Exception\InvalidArgumentException;

use function array_is_list;

/**
 * A SQL condition fragment with its positional parameter values.
 *
 * The SQL fragment is interpolated directly into the generated query. Only the parameter values are bound safely
 * through PDO placeholders. The SQL string must therefore be trusted and must not be derived from untrusted input.
 */
final readonly class SqlCondition
{
    /**
     * @var list<scalar|null>
     */
    private array $parameters;

    /**
     * @param scalar|null ...$parameters
     */
    public function __construct(
        private string $sql,
        string|int|float|bool|null ...$parameters,
    ) {
        if (! array_is_list($parameters)) {
            throw new InvalidArgumentException('SqlCondition parameters must be passed positionally.');
        }

        $this->parameters = $parameters;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * @return list<scalar|null>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
