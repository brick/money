<?php

namespace Brick\Money\Context;

use Brick\Money\Context;
use Brick\Money\Currency;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;

/**
 * Adjusts the scale of the result to the default scale for the currency in use.
 * Adjustments are performed in step 1.
 */
class DefaultContext implements Context
{
    /**
     * @var int
     */
    private $roundingMode;

    /**
     * @param int $roundingMode
     */
    public function __construct($roundingMode = RoundingMode::UNNECESSARY)
    {
        $this->roundingMode = $roundingMode;
    }

    /**
     * @inheritdoc
     */
    public function applyTo(BigNumber $amount, Currency $currency)
    {
        return $amount->toScale($currency->getDefaultFractionDigits(), $this->roundingMode);
    }

    /**
     * {@inheritdoc}
     */
    public function getStep()
    {
        return 1;
    }
}
