<?php

declare(strict_types=1);

namespace Brick\Money\Context;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Context;
use Brick\Money\Currency;
use Brick\Money\Exception\InvalidArgumentException;
use Override;

/**
 * Adjusts a number to a custom scale and optionally step.
 */
final readonly class CustomContext implements Context
{
    /**
     * @param non-negative-int $scale The scale of the monies using this context.
     * @param positive-int     $step  An optional cash rounding step. Must either divide 10^scale or be a multiple of 10^scale.
     *                                For example, scale=2 and step=5 allows 0.00, 0.05, 0.10, etc.
     *                                And scale=2 and step=1000 allows 0.00, 10.00, 20.00, etc.
     */
    public function __construct(
        private int $scale,
        private int $step = 1,
    ) {
        /** @psalm-suppress DocblockTypeContradiction, NoValue */
        if ($scale < 0) {
            throw InvalidArgumentException::invalidScale($scale);
        }

        if ($step < 1 || ! $this->isValidStepForScale($scale, $step)) {
            throw InvalidArgumentException::invalidStep($step);
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
     *
     * @return non-negative-int
     */
    public function getScale(): int
    {
        return $this->scale;
    }

    /**
     * @param non-negative-int $scale
     * @param positive-int     $step
     */
    private function isValidStepForScale(int $scale, int $step): bool
    {
        $step = BigInteger::of($step);
        $power = BigInteger::ten()->power($scale);

        return $power->mod($step)->isZero() || $step->mod($power)->isZero();
    }
}
