<?php

declare(strict_types=1);

namespace Brick\Money\Context;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Context;
use Brick\Money\Currency;
use InvalidArgumentException;
use Override;

/**
 * Automatically adjusts the scale of a number to the strict minimum.
 */
final class AutoContext implements Context
{
    #[Override]
    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode): BigDecimal
    {
        if ($roundingMode !== RoundingMode::UNNECESSARY) {
            throw new InvalidArgumentException('AutoContext only supports RoundingMode::UNNECESSARY');
        }

        return $amount->toBigDecimal()->stripTrailingZeros();
    }

    #[Override]
    public function getStep(): int
    {
        return 1;
    }

    #[Override]
    public function isFixedScale(): bool
    {
        return false;
    }
}
