<?php

namespace Brick\Money\MoneyContext;

use Brick\Money\Currency;
use Brick\Money\Money;
use Brick\Money\MoneyContext;

use Brick\Math\BigNumber;

/**
 * Returns an exact result, adjusting the scale as required. The resulting step is 1.
 */
class ExactContext implements MoneyContext
{
    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency)
    {
        return new Money($amount->toBigDecimal(), $currency);
    }
}
