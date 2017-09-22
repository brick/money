<?php

namespace Brick\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\RoundingNecessaryException;

/**
 * Adjusts a money operation result to a given scale & step.
 */
interface Adjustment
{
    /**
     * @param BigNumber $amount   The amount to scale.
     * @param Currency  $currency The target currency.
     *
     * @return Money A Money with the adjustment applied.
     *
     * @throws RoundingNecessaryException If the result cannot be represented at the required scale without rounding.
     */
    public function applyTo(BigNumber $amount, Currency $currency);
}
