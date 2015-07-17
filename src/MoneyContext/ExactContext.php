<?php

namespace Brick\Money\MoneyContext;

use Brick\Money\Currency;
use Brick\Money\MoneyContext;

use Brick\Math\BigNumber;

/**
 * Returns an exact result, adjusting the scale as required.
 */
class ExactContext implements MoneyContext
{
    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency, $currentScale)
    {
        return $amount->toBigDecimal();
    }
}
