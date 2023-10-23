<?php

declare(strict_types=1);

namespace Brick\Money\Context;

use Brick\Money\Context;
use Brick\Money\Currency;

use Brick\Math\BigDecimal;
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
     */
    private int $step;

    /**
     * @param int $step The cash rounding step, in minor units. Must be a multiple of 2 and/or 5.
     */
    public function __construct(int $step)
    {
        $this->step = $step;
    }

    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency, int $roundingMode) : BigDecimal
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
    public function getStep() : int
    {
        return $this->step;
    }

    /**
     * {@inheritdoc}
     */
    public function isFixedScale() : bool
    {
        return true;
    }
}
