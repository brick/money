<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\ComparisonMode\BaseCurrencyMode;
use Brick\Money\ComparisonMode\PairwiseMode;
use Brick\Money\Exception\ExchangeRateNotFoundException;
use Brick\Money\Exception\ExchangeRateProviderException;
use Brick\Money\Exception\InvalidArgumentException;

/**
 * Strategy for comparing two monetary values, potentially in different currencies.
 *
 * This interface is sealed: implementing it in userland code is not supported, and breaking changes to this interface
 * can happen at any time, even in minor or patch releases.
 *
 * @phpstan-sealed PairwiseMode|BaseCurrencyMode
 */
interface ComparisonMode
{
    /**
     * Compares two monetary values.
     *
     * @internal
     *
     * @param array<string, mixed> $dimensions Additional exchange-rate lookup dimensions (e.g., date or rate type).
     *
     * @return -1|0|1
     *
     * @throws InvalidArgumentException      If this mode does not support the operand types.
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function compare(Monetary $a, Monetary $b, CurrencyConverter $converter, array $dimensions): int;
}
