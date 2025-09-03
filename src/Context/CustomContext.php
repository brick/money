<?php

declare(strict_types=1);

namespace Brick\Money\Context;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Context;
use Brick\Money\Currency;

/**
 * Adjusts a number to a custom scale, and optionally step.
 */
final class CustomContext implements Context
{
    /**
     * The scale of the monies using this context.
     */
    private readonly int $scale;

    /**
     * An optional cash rounding step. Must be a multiple of 2 and/or 5.
     *
     * For example, scale=4 and step=5 would allow amounts of 0.0000, 0.0005, 0.0010, etc.
     */
    private readonly int $step;

    /**
     * @param int $scale The scale of the monies using this context.
     * @param int $step  An optional cash rounding step. Must be a multiple of 2 and/or 5.
     */
    public function __construct(int $scale, int $step = 1)
    {
        $this->scale = $scale;
        $this->step  = $step;
    }

    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode) : BigDecimal
    {
        if ($this->step === 1) {
            return $amount->toScale($this->scale, $roundingMode);
        }

        return $amount
            ->toBigRational()
            ->dividedBy($this->step)
            ->toScale($this->scale, $roundingMode)
            ->multipliedBy($this->step);
    }

    public function getStep() : int
    {
        return $this->step;
    }

    public function isFixedScale() : bool
    {
        return true;
    }

    /**
     * Returns the scale used by this context.
     *
     * @return int
     */
    public function getScale() : int
    {
        return $this->scale;
    }
}
