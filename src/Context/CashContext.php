<?php

namespace Brick\Money\Context;

use Brick\Money\Context;
use Brick\Money\Currency;

use Brick\Math\BigNumber;

/**
 * Adjusts a number to the default scale for the currency, respecting a cash rounding.
 */
final class CashContext implements Context
{
    /**
     * The cash rounding step, in minor units.
     *
     * For example, step 5 on CHF would allow CHF 0.00, CHF 0.05, CHF 0.10, etc.
     *
     * @var int
     */
    private $step;

    /**
     * @param int $step The cash rounding step, in minor units. Must be a multiple of 2 and/or 5.
     */
    public function __construct($step)
    {
        $this->step = (int) $step;
    }

    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency, $roundingMode)
    {
        $scale = $currency->getDefaultFractionDigits();

        if ($this->step === 1) {
            return $amount->toScale($scale, $roundingMode);
        }

        return $amount
            ->toBigRational()
            ->dividedBy($this->step)
            ->toScale($scale, $roundingMode)
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
}
