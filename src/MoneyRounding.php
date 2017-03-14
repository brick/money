<?php

namespace Brick\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;

/**
 * A interface that defines how to round a number.
 */
interface MoneyRounding
{
    /**
     * @param BigNumber $number The number to round.
     * @param int       $scale  The scale of the resulting number.
     *
     * @return BigDecimal The rounded number, at the given scale.
     */
    public function round(BigNumber $number, $scale);
}
