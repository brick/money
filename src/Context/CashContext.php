<?php

declare(strict_types=1);

namespace Brick\Money\Context;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Brick\Money\Context;
use Brick\Money\Context\Internal\StepValidation;
use Brick\Money\Currency;
use Brick\Money\Exception\ContextException;
use Brick\Money\Exception\InvalidArgumentException;
use Override;

use function sprintf;

/**
 * Adjusts a number to the default scale for the currency, respecting a cash rounding.
 */
final readonly class CashContext implements Context
{
    use StepValidation;

    /**
     * @param positive-int $step The cash rounding step, in minor units. Must either divide 10^scale or be a multiple
     *                           of 10^scale, where scale is the scale of the money this context is applied to.
     *                           For example, step 5 on CHF would allow CHF 0.00, CHF 0.05, CHF 0.10, etc.
     *
     * @pure
     */
    public function __construct(
        private int $step,
    ) {
        /** @phpstan-ignore smaller.alwaysFalse */
        if ($step < 1) {
            throw InvalidArgumentException::invalidStep($step);
        }
    }

    #[Override]
    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode): BigDecimal
    {
        $scale = $currency->getDefaultFractionDigits();

        if ($this->step === 1) {
            return $amount->toScale($scale, $roundingMode);
        }

        if (! $this->isValidStepForScale($this->step, $scale)) {
            throw ContextException::invalidStepForScale($this->step, $scale);
        }

        return $amount
            ->toBigRational()
            ->dividedBy($this->step)
            ->toScale($scale, $roundingMode)
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

    #[Override]
    public function isEqualTo(Context $context): bool
    {
        return $context instanceof CashContext
            && $context->step === $this->step;
    }

    #[Override]
    public function __toString(): string
    {
        return sprintf('CashContext(step=%d)', $this->step);
    }
}
