<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;

/**
 * Adjusts a rational number to a decimal amount.
 */
interface Context
{
    /**
     * Applies this context to a rational amount, and returns a decimal number.
     *
     * The given rounding mode MUST be respected; no default rounding mode must be applied.
     * In case the rounding mode is irrelevant, for example in AutoContext, this method MUST throw an exception if a
     * rounding mode other than RoundingMode::UNNECESSARY is used.
     *
     * @psalm-param RoundingMode::* $roundingMode
     *
     * @param BigNumber $amount       The amount.
     * @param Currency  $currency     The target currency.
     * @param int       $roundingMode The rounding mode.
     *
     * @return BigDecimal
     *
     * @throws RoundingNecessaryException If the result cannot be represented at the required scale without rounding.
     */
    public function applyTo(BigNumber $amount, Currency $currency, int $roundingMode) : BigDecimal;

    /**
     * Returns the step used by this context.
     *
     * If no cash rounding is involved, this must return 1.
     * This value is used by money allocation methods that do not go through the applyTo() method.
     *
     * @return int
     */
    public function getStep() : int;

    /**
     * Returns whether this context uses a fixed scale and step.
     *
     * When the scale and step are fixed, it is considered safe to add or subtract monies amounts directly —as long as
     * they are in the same context— without going through the applyTo() method, allowing for an optimization.
     *
     * @return bool
     */
    public function isFixedScale() : bool;
}
