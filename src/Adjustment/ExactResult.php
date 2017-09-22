<?php

namespace Brick\Money\Adjustment;

use Brick\Money\Adjustment;
use Brick\Money\Currency;

use Brick\Math\BigNumber;

/**
 * Returns an exact result, adjusting the scale to the minimum required.
 * Adjustments are performed in step 1.
 */
class ExactResult implements Adjustment
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
