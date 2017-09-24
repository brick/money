<?php

namespace Brick\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\RoundingNecessaryException;

/**
 * Adjusts an operation result to a decimal amount.
 */
interface Context
{
    /**
     * Applies this context to a rational amount, and returns a decimal number.
     *
     * @param BigNumber $amount   The amount.
     * @param Currency  $currency The target currency.
     *
     * @return BigDecimal
     *
     * @throws RoundingNecessaryException If the result cannot be represented at the required scale without rounding.
     */
    public function applyTo(BigNumber $amount, Currency $currency);

    /**
     * Returns the step used by this context.
     *
     * @return int
     */
    public function getStep();
}
