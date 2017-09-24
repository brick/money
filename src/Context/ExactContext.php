<?php

namespace Brick\Money\Context;

use Brick\Money\Context;
use Brick\Money\Currency;

use Brick\Math\BigNumber;

/**
 * Returns an exact result, adjusting the scale to the minimum required.
 * Adjustments are performed in step 1.
 */
class ExactContext implements Context
{
    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency)
    {
        return $amount->toBigDecimal()->stripTrailingZeros();
    }

    /**
     * {@inheritdoc}
     */
    public function getStep()
    {
        return 1;
    }
}
