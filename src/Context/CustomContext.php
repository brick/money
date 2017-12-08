<?php

namespace Brick\Money\Context;

use Brick\Money\Context;
use Brick\Money\Currency;

use Brick\Math\BigNumber;

/**
 * Adjusts a number to a custom scale, and optionally step.
 */
final class CustomContext implements Context
{
    /**
     * The scale of the monies using this context.
     *
     * @var int
     */
    private $scale;

    /**
     * An optional cash rounding step. Must be a multiple of 2 and/or 5.
     *
     * For example, scale=4 and step=5 would allow amounts of 0.0000, 0.0005, 0.0010, etc.
     *
     * @var int
     */
    private $step;

    /**
     * @param int $scale The scale of the monies using this context.
     * @param int $step  An optional cash rounding step. Must be a multiple of 2 and/or 5.
     */
    public function __construct($scale, $step = 1)
    {
        $this->scale = (int) $scale;
        $this->step  = (int) $step;
    }

    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency, $roundingMode)
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

    /**
     * {@inheritdoc}
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * {@inheritdoc}
     */
    public function isFixedScale()
    {
        return true;
    }

    /**
     * Returns the scale used by this context.
     *
     * @return int
     */
    public function getScale()
    {
        return $this->scale;
    }
}
