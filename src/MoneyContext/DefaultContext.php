<?php

namespace Brick\Money\MoneyContext;

use Brick\Money\Currency;
use Brick\Money\MoneyContext;
use Brick\Money\MoneyRounding;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;

/**
 * Adjusts the scale of the result to the default scale for the currency in use.
 */
class DefaultContext implements MoneyContext
{
    /**
     * @var MoneyRounding
     */
    private $rounding;

    /**
     * @param MoneyRounding $rounding
     */
    public function __construct(MoneyRounding $rounding)
    {
        $this->rounding = $rounding;
    }

    /**
     * @inheritdoc
     */
    public function applyTo(BigNumber $amount, Currency $currency, $currentScale)
    {
        return $this->rounding->round($amount, $currency->getDefaultFractionDigits());
    }
}
