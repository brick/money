<?php

declare(strict_types=1);

namespace Brick\Money\Context;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Context;
use Brick\Money\Currency;
use Brick\Money\Exception\ContextException;
use Override;

/**
 * Automatically adjusts the scale of a number to the strict minimum.
 */
final readonly class AutoContext implements Context
{
    #[Override]
    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode): BigDecimal
    {
        if ($roundingMode !== RoundingMode::Unnecessary) {
            throw ContextException::autoContextRoundingMode();
        }

        return $amount->toBigDecimal()->strippedOfTrailingZeros();
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
