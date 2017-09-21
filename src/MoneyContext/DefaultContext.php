<?php

namespace Brick\Money\MoneyContext;

use Brick\Math\RoundingMode;
use Brick\Money\Currency;
use Brick\Money\Money;
use Brick\Money\MoneyContext;

use Brick\Math\BigNumber;

/**
 * Adjusts the scale of the result to the default scale for the currency in use.
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
    public function __construct($roundingMode = RoundingMode::UNNECESSARY)
    {
        $this->roundingMode = $roundingMode;
    }

    /**
     * @inheritdoc
     */
    public function applyTo(BigNumber $amount, Currency $currency)
    {
        $amount = $amount->toScale($currency->getDefaultFractionDigits(), $this->roundingMode);

        return new Money($amount, $currency);
    }
}
