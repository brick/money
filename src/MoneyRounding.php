<?php

namespace Brick\Money;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;

interface MoneyRounding
{
    /**
     * @param BigNumber $number
     * @param int       $scale
     *
     * @return BigDecimal
     */
    public function round(BigNumber $number, $scale);
}
