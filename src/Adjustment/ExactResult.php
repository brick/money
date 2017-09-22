<?php

namespace Brick\Money\Adjustment;

use Brick\Money\Adjustment;
use Brick\Money\Currency;
use Brick\Money\Money;

use Brick\Math\BigNumber;

/**
 * Returns an exact result, adjusting the scale as required. The resulting step is 1.
 */
class ExactResult implements Adjustment
{
    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency)
    {
        return new Money($amount->toBigDecimal(), $currency);
    }
}
