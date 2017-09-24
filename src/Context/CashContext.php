<?php

namespace Brick\Money\Context;

use Brick\Money\Context;
use Brick\Money\Currency;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;

/**
 * Adjusts the result to the currency's default scale, applying a cash rounding.
 */
class CashContext implements Context
{
    /**
     * @var int
     */
    private $step;

    /**
     * @var int
     */
    private $roundingMode;

    /**
     * @param int $step
     * @param int $roundingMode
     */
    public function __construct($step = 1, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $this->step         = (int) $step;
        $this->roundingMode = (int) $roundingMode;
    }

    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency)
    {
        $scale = $currency->getDefaultFractionDigits();

        if ($this->step === 1) {
            return $amount->toScale($scale, $this->roundingMode);
        }

        return $amount
            ->toBigRational()
            ->dividedBy($this->step)
            ->toScale($scale, $this->roundingMode)
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
