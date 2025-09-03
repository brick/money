<?php

declare(strict_types=1);

namespace Brick\Money\Context;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Context;
use Brick\Money\Currency;

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
    private readonly int $step;

    /**
     * @param int $step The cash rounding step, in minor units. Must be a multiple of 2 and/or 5.
     */
    public function __construct(int $step)
    {
        $this->step = $step;
    }

    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode): BigDecimal
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

    public function getStep(): int
    {
        return $this->step;
    }

    public function isFixedScale(): bool
    {
        return true;
    }
}
