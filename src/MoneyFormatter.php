<?php
/**
 * @author Maciej Klepaczewski <matt@fasterwebsite.com>
 * @link https://fasterwebsite.com/
 * @copyright Copyright (c) 2025, Maciej Klepaczewski FasterWebsite.com
 */
declare(strict_types=1);

namespace Brick\Money;

interface MoneyFormatter {
    public function format(Money $money): string;
}