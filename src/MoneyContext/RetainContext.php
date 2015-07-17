<?php

namespace Brick\Money\MoneyContext;

use Brick\Money\Currency;
use Brick\Money\MoneyContext;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;

/**
 * Applies the scale of the current Money to the result.
 */
class RetainContext implements MoneyContext
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
        $this->roundingMode = (int) $roundingMode;
    }

    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency, $currentScale)
    {
        return $amount->toScale($currentScale, $this->roundingMode);
    }
}
