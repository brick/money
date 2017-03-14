<?php

namespace Brick\Money\MoneyContext;

use Brick\Money\Currency;
use Brick\Money\MoneyContext;
use Brick\Money\MoneyRounding;

use Brick\Math\BigNumber;

/**
 * Adjusts the scale of the result to a fixed value.
 */
class FixedContext implements MoneyContext
{
    /**
     * @var int
     */
    private $scale;

    /**
     * @var MoneyRounding
     */
    private $rounding;

    /**
     * @param int           $scale
     * @param MoneyRounding $rounding
     */
    public function __construct($scale, MoneyRounding $rounding)
    {
        $this->scale    = (int) $scale;
        $this->rounding = $rounding;
    }

    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency, $currentScale)
    {
        return $this->rounding->round($amount, $this->scale);
    }
}
