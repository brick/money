<?php

namespace Brick\Money\Adjustment;

use Brick\Money\Adjustment;
use Brick\Money\Currency;
use Brick\Money\Money;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;

/**
 * Adjusts the scale of the result to the default scale for the currency in use. The resulting step is 1.
 */
class DefaultScale implements Adjustment
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
