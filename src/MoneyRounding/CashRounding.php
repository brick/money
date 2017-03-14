<?php

namespace Brick\Money\MoneyRounding;

use Brick\Math\BigNumber;
use Brick\Money\MoneyRounding;

/**
 * Rounds cash monies to the nearest multiple of the given minor unit.
 */
class CashRounding implements MoneyRounding
{
    /**
     * @var int
     */
    private $step;

    /**
     * The rounding mode, one of the Brick\Math\RoundingMode constants.
     *
     * @var int
     */
    private $roundingMode;

    /**
     * @param int $step         The step in minor units, such as 2, 5, 10, 20, 50, 100, etc.
     * @param int $roundingMode One of the Brick\Math\RoundingMode constants.
     */
    public function __construct($step, $roundingMode)
    {
        $this->step         = $step;
        $this->roundingMode = $roundingMode;
    }

    /**
     * @inheritdoc
     */
    public function round(BigNumber $number, $scale)
    {
        return $number
            ->toBigRational()
            ->dividedBy($this->step)
            ->toScale($scale, $this->roundingMode)
            ->multipliedBy($this->step);
    }
}
