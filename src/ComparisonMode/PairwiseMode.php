<?php

declare(strict_types=1);

namespace Brick\Money\ComparisonMode;

use Brick\Money\AbstractMoney;
use Brick\Money\ComparisonMode;
use Brick\Money\CurrencyConverter;
use Brick\Money\Exception\InvalidArgumentException;
use Brick\Money\Monetary;
use Override;

/**
 * Compares two monies by converting A into B's currency without rounding, then comparing.
 *
 * This is the most precise mode for individual pair comparisons. However, if the exchange rate provider uses
 * asymmetric rates (where the rate from X to Y is not the exact reciprocal of Y to X), comparisons can produce
 * contradictory results: A < B, B < C, and C < A can all hold simultaneously, which means min()/max() results
 * will depend on argument order.
 *
 * Both operands must be Money or RationalMoney. MoneyBag is not supported: the second operand must have a single
 * currency to serve as the conversion target, and the first operand's result is compared to it directly.
 */
final readonly class PairwiseMode implements ComparisonMode
{
    #[Override]
    public function compare(Monetary $a, Monetary $b, CurrencyConverter $converter, array $dimensions): int
    {
        if (! $a instanceof AbstractMoney || ! $b instanceof AbstractMoney) {
            throw InvalidArgumentException::pairwiseDoesNotSupportMoneyBag();
        }

        return $converter->convertToRational($a, $b->getCurrency(), $dimensions)->compareTo($b);
    }
}
