<?php

namespace Brick\Money\Context;

use Brick\Money\Context;
use Brick\Money\Currency;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;

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
     * @var int
     */
    private $roundingMode;

    /**
     * @param int $scale
     * @param int $step
     * @param int $roundingMode
     */
    public function __construct($scale, $step = 1, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $this->scale        = (int) $scale;
        $this->step         = (int) $step;
        $this->roundingMode = (int) $roundingMode;
    }

    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency)
    {
        if ($this->step === 1) {
            return $amount->toScale($this->scale, $this->roundingMode);
        }

        return $amount
            ->toBigRational()
            ->dividedBy($this->step)
            ->toScale($this->scale, $this->roundingMode)
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
