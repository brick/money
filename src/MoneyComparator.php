<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\Exception\ExchangeRateNotFoundException;
use Brick\Money\Exception\ExchangeRateProviderException;
use Brick\Money\Exception\InvalidArgumentException;

/**
 * Compares monies in different currencies, using an ExchangeRateProvider and a ComparisonMode.
 *
 * Two built-in modes are available:
 *
 * - PairwiseMode: A is converted into B's currency without rounding, then compared. Most precise for
 *   individual pairs, but with asymmetric rates, contradictory results are possible (A < B, B < C, C < A).
 *   Accepts Money and RationalMoney.
 *
 * - BaseCurrencyMode: both operands are converted to a base currency before comparing. Results are
 *   always consistent (if A ≤ B and B ≤ C then A ≤ C), so min()/max() do not depend on argument order.
 *   Accepts Money, RationalMoney, and MoneyBag.
 */
final readonly class MoneyComparator
{
    private CurrencyConverter $currencyConverter;

    /**
     * @param array<string, mixed> $dimensions Additional exchange-rate lookup dimensions (e.g., date or rate type).
     */
    public function __construct(
        ExchangeRateProvider $exchangeRateProvider,
        private ComparisonMode $mode,
        private array $dimensions = [],
    ) {
        $this->currencyConverter = new CurrencyConverter($exchangeRateProvider);
    }

    /**
     * Compares the given monies.
     *
     * @return -1|0|1
     *
     * @throws InvalidArgumentException      If the mode does not support the given operand types.
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function compare(Monetary $a, Monetary $b): int
    {
        return $this->mode->compare($a, $b, $this->currencyConverter, $this->dimensions);
    }

    /**
     * @throws InvalidArgumentException      If the mode does not support the given operand types.
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function isEqual(Monetary $a, Monetary $b): bool
    {
        return $this->compare($a, $b) === 0;
    }

    /**
     * @throws InvalidArgumentException      If the mode does not support the given operand types.
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function isLess(Monetary $a, Monetary $b): bool
    {
        return $this->compare($a, $b) < 0;
    }

    /**
     * @throws InvalidArgumentException      If the mode does not support the given operand types.
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function isLessOrEqual(Monetary $a, Monetary $b): bool
    {
        return $this->compare($a, $b) <= 0;
    }

    /**
     * @throws InvalidArgumentException      If the mode does not support the given operand types.
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function isGreater(Monetary $a, Monetary $b): bool
    {
        return $this->compare($a, $b) > 0;
    }

    /**
     * @throws InvalidArgumentException      If the mode does not support the given operand types.
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function isGreaterOrEqual(Monetary $a, Monetary $b): bool
    {
        return $this->compare($a, $b) >= 0;
    }

    /**
     * Returns the smallest of the given monies.
     *
     * The monies are compared from left to right. If several monies are equal to the minimum value, the first one
     * is returned.
     *
     * @template T of Monetary
     *
     * @param T $money     The first money.
     * @param T ...$monies The subsequent monies.
     *
     * @return T The smallest money.
     *
     * @throws InvalidArgumentException      If the mode does not support the given operand types.
     * @throws ExchangeRateNotFoundException If an exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function min(Monetary $money, Monetary ...$monies): Monetary
    {
        $min = $money;

        foreach ($monies as $money) {
            if ($this->isGreater($min, $money)) {
                $min = $money;
            }
        }

        return $min;
    }

    /**
     * Returns the largest of the given monies.
     *
     * The monies are compared from left to right. If several monies are equal to the maximum value, the first one
     * is returned.
     *
     * @template T of Monetary
     *
     * @param T $money     The first money.
     * @param T ...$monies The subsequent monies.
     *
     * @return T The largest money.
     *
     * @throws InvalidArgumentException      If the mode does not support the given operand types.
     * @throws ExchangeRateNotFoundException If an exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function max(Monetary $money, Monetary ...$monies): Monetary
    {
        $max = $money;

        foreach ($monies as $money) {
            if ($this->isLess($max, $money)) {
                $max = $money;
            }
        }

        return $max;
    }
}
