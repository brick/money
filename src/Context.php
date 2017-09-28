<?php

namespace Brick\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\RoundingNecessaryException;

/**
 * Adjusts a rational number to a decimal amount.
 */
interface Context
{
    /**
     * Applies this context to a rational amount, and returns a decimal number.
     *
     * The given rounding mode MUST be respected; no default rounding mode must be applied.
     * In case the rounding mode is irrelevant, for example in ExactContext, this method MUST throw an exception if a
     * rounding mode other than RoundingMode::UNNECESSARY is used.
     *
     * @param BigNumber $amount       The amount.
     * @param Currency  $currency     The target currency.
     * @param int       $roundingMode The rounding mode.
     *
     * @return BigDecimal
     *
     * @throws RoundingNecessaryException If the result cannot be represented at the required scale without rounding.
     */
    public function applyTo(BigNumber $amount, Currency $currency, $roundingMode);

    /**
     * Returns the step used by this context.
     *
     * If no cash rounding is involved, this must return 1.
     *
     * @return int
     */
    public function getStep();
}