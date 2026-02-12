<?php

declare(strict_types=1);

namespace Brick\Money\Context;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Context;
use Brick\Money\Currency;
use Brick\Money\Exception\MoneyException;
use Override;

use function sprintf;

/**
 * Adjusts a number to a custom scale and optionally step.
 */
final readonly class CustomContext implements Context
{
    /**
     * @param non-negative-int $scale The scale of the monies using this context.
     * @param int<1, max>      $step  An optional cash rounding step. Must either divide 10^scale or be a multiple of 10^scale.
     *                                For example, scale=2 and step=5 allows 0.00, 0.05, 0.10, etc.
     *                                And scale=2 and step=1000 allows 0.00, 10.00, 20.00, etc.
     */
    public function __construct(
        private int $scale,
        private int $step = 1,
    ) {
        if (! $this->isValidStep($scale, $step)) {
            throw new MoneyException(sprintf('Invalid step: %d.', $step));
        }
    }

    #[Override]
    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode): BigDecimal
    {
        if ($this->step === 1) {
            return $amount->toScale($this->scale, $roundingMode);
        }

        return $amount
            ->toBigRational()
            ->dividedBy($this->step)
            ->toScale($this->scale, $roundingMode)
            ->multipliedBy($this->step);
    }

    #[Override]
    public function getStep(): int
    {
        return $this->step;
    }

    #[Override]
    public function isFixedScale(): bool
    {
        return true;
    }

    /**
     * Returns the scale used by this context.
     */
    public function getScale(): int
    {
        return $this->scale;
    }

    private function isValidStep(int $scale, int $step): bool
    {
        if ($step < 1) {
            return false;
        }

        $power = 10 ** $scale;

        return $power % $step === 0 || $step % $power === 0;
    }
}
