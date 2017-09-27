<?php

namespace Brick\Money\Context;

use Brick\Money\Context;
use Brick\Money\Currency;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;

/**
 * Adjust the scale of an operation result to return an exact result.
 */
class ExactContext implements Context
{
    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency, $roundingMode)
    {
        if ($roundingMode !== RoundingMode::UNNECESSARY) {
            throw new \InvalidArgumentException('ExactContext only supports RoundingMode::UNNECESSARY');
        }

        return $amount->toBigDecimal();
    }

    /**
     * {@inheritdoc}
     */
    public function getStep()
    {
        return 1;
    }
}
