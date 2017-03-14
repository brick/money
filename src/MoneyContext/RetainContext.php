<?php

namespace Brick\Money\MoneyContext;

use Brick\Money\Currency;
use Brick\Money\MoneyContext;
use Brick\Money\MoneyRounding;

use Brick\Math\BigNumber;

/**
 * Applies the scale of the current Money to the result.
 */
class RetainContext implements MoneyContext
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
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency, $currentScale)
    {
        return $this->rounding->round($amount, $currentScale);
    }
}
