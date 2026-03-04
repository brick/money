<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Exception\ContextException;
use Stringable;

/**
 * Adjusts a rational number to a decimal amount.
 */
interface Context extends Stringable
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
     * This value is used by money allocation methods that do not go through the applyTo() method.
     *
     * @return positive-int
     *
     * @pure
     */
    public function getStep(): int;

    /**
     * Returns whether this context produces a deterministic scale regardless of the input value.
     *
     * DefaultContext, CashContext, and CustomContext are all fixed.
     * AutoContext is not fixed: the scale depends on the value being stored.
     *
     * When false, operations such as quotient(), remainder(), allocate(), and split() throw a ContextException.
     * Their results depend on the scale, which with a non-fixed-scale context is a property of the runtime value.
     *
     * @pure
     */
    public function isFixedScale(): bool;

    /**
     * Returns a string representation of this Context, for debugging purposes.
     *
     * @pure
     */
    public function __toString(): string;
}
