<?php

declare(strict_types=1);

namespace Brick\Money\Context;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Context;
use Brick\Money\Currency;

/**
 * Adjusts a number to the default scale for the currency.
 */
final class DefaultContext implements Context
{
    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode): BigDecimal
    {
        return $amount->toScale($currency->getDefaultFractionDigits(), $roundingMode);
    }

    public function getStep(): int
    {
        return 1;
    }

    public function isFixedScale(): bool
    {
        return true;
    }
}
