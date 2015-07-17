<?php

namespace Brick\Money\MoneyContext;

use Brick\Money\Currency;
use Brick\Money\MoneyContext;

use Brick\Math\BigNumber;

/**
 * Adjusts the scale of the result to the default value for the currency in use.
 */
class DefaultContext implements MoneyContext
{
    /**
     * @var int
     */
    private $roundingMode;

    /**
     * @param int $roundingMode
     */
    public function __construct($roundingMode)
    {
        $this->roundingMode = (int) $roundingMode;
    }

    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency)
    {
        return $amount->toScale($currency->getDefaultFractionDigits(), $this->roundingMode);
    }
}
