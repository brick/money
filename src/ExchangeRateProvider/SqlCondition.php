<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Money\Exception\InvalidArgumentException;

use function array_is_list;

/**
 * A SQL condition fragment with its positional parameter values.
 */
final readonly class SqlCondition
{
    /**
     * @var list<scalar|null>
     */
    private array $parameters;

    /**
     * @param string      $sql           The SQL condition fragment that may include `?` placeholders.
     * @param scalar|null ...$parameters The positional parameter values, one for each `?` placeholder.
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
