<?php

namespace Brick\Money\Context;

use Brick\Money\Context;
use Brick\Money\Currency;

use Brick\Math\BigNumber;

/**
 * Adjusts the scale & step of the result to custom values.
 */
class PrecisionContext implements Context
{
    /**
     * @var int
     */
    private $scale;

    /**
     * @var int
     */
    private $step;

    /**
     * @param int $scale
     * @param int $step
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
}
