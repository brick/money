<?php

declare(strict_types=1);

namespace Brick\Money;

interface MoneyFormatter
{
    public function format(Money $money): string;
}
