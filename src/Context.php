<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Exception\ContextException;

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
     * rounding mode other than RoundingMode::Unnecessary is used.
     *
     * @param BigNumber    $amount       The amount.
     * @param Currency     $currency     The target currency.
     * @param RoundingMode $roundingMode The rounding mode.
     *
     * @throws ContextException           If the context does not apply.
     * @throws RoundingNecessaryException If the result cannot be represented at the required scale without rounding.
     *
     * @pure
     */
    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode): BigDecimal;

    /**
     * Returns the step used by this context.
     *
     * If no cash rounding is involved, this must return 1.
     *
     * @return positive-int
     *
     * @pure
     */
    public function getStep(): int;

    /**
     * Returns whether this context uses a fixed scale and step.
     *
     * When the scale and step are fixed, it is considered safe to add or subtract monies amounts directly —as long as
     * they are in the same context— without going through the applyTo() method, allowing for an optimization.
     *
     * @pure
     */
    public function isFixedScale(): bool;
}
