<?php

namespace Brick\Money\Context;

use Brick\Money\Context;
use Brick\Money\Currency;

use Brick\Math\BigNumber;

/**
 * Adjusts a number to the default scale for the currency.
 */
class DefaultContext implements Context
{
    /**
     * @inheritdoc
     */
    public function applyTo(BigNumber $amount, Currency $currency, $roundingMode)
    {
        return $amount->toScale($currency->getDefaultFractionDigits(), $roundingMode);
    }

    /**
     * {@inheritdoc}
     */
    public function getStep()
    {
        return 1;
    }
}