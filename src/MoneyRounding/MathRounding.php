<?php

namespace Brick\Money\MoneyRounding;

use Brick\Math\BigNumber;
use Brick\Money\MoneyRounding;

/**
 * Rounds monies using a mathematical rounding.
 */
class MathRounding implements MoneyRounding
{
    /**
     * The rounding mode, one of the Brick\Math\RoundingMode constants.
     *
     * @var int
     */
    private $roundingMode;

    /**
     * @param int $roundingMode One of the Brick\Math\RoundingMode constants.
     */
    public function __construct($roundingMode)
    {
        $this->roundingMode = $roundingMode;
    }

    /**
     * @inheritdoc
     */
    public function round(BigNumber $number, $scale)
    {
        return $number->toScale($scale, $this->roundingMode);
    }
}
