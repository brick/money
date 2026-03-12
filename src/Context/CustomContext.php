<?php

declare(strict_types=1);

namespace Brick\Money\Context;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Context;
use Brick\Money\Context\Internal\StepValidation;
use Brick\Money\Currency;
use Brick\Money\Exception\InvalidArgumentException;
use Override;

use function sprintf;

/**
 * Adjusts a number to a custom scale and optionally step.
 */
final readonly class CustomContext implements Context
{
    use StepValidation;

    /**
     * @param non-negative-int $scale The scale of the monies using this context.
     * @param positive-int     $step  An optional cash rounding step. Must either divide 10^scale or be a multiple of 10^scale.
     *                                For example, scale=2 and step=5 allows 0.00, 0.05, 0.10, etc.
     *                                And scale=2 and step=1000 allows 0.00, 10.00, 20.00, etc.
     *
     * @pure
     */
    public function __construct(
        private int $scale,
        private int $step = 1,
    ) {
        /** @phpstan-ignore smaller.alwaysFalse */
        if ($scale < 0) {
            throw InvalidArgumentException::invalidScale($scale);
        }

        /** @phpstan-ignore smaller.alwaysFalse */
        if ($step < 1) {
            throw InvalidArgumentException::invalidStep($step);
        }

        if (! $this->isValidStepForScale($step, $scale)) {
            throw InvalidArgumentException::invalidStepForScale($step, $scale);
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
     *
     * @pure
     */
    public function getScale(): int
    {
        return $this->scale;
    }

    #[Override]
    public function isEqualTo(Context $context): bool
    {
        return $context instanceof CustomContext
            && $context->scale === $this->scale
            && $context->step === $this->step;
    }

    #[Override]
    public function __toString(): string
    {
        return sprintf('CustomContext(scale=%d, step=%d)', $this->scale, $this->step);
    }
}
