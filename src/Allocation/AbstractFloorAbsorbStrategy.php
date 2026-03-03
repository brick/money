<?php

declare(strict_types=1);

namespace Brick\Money\Allocation;

use Brick\Math\BigInteger;

use function array_values;

/**
 * @internal
 */
abstract readonly class AbstractFloorAbsorbStrategy extends AbstractFloorStrategy
{
    final public function allocate(BigInteger $amountInSteps, array $ratios): array
    {
        [$floors, $remainderSteps] = $this->computeFloors($amountInSteps, $ratios);

        $indices = $this->prioritizeIndices($amountInSteps, $ratios);

        for ($i = 0, $r = $remainderSteps->toInt(); $i < $r; $i++) {
            $floors[$indices[$i]] = $floors[$indices[$i]]->plus(1);
        }

        return array_values($floors); // phpstan does not see $floors as a list anymore without array_values()
    }

    /**
     * Returns the allocatee offsets in priority order for receiving remainder steps.
     *
     * Implementations must return each offset of $ratios exactly once.
     *
     * @param non-empty-list<BigInteger> $ratios
     *
     * @return non-empty-list<int>
     *
     * @pure
     */
    abstract protected function prioritizeIndices(BigInteger $amountInSteps, array $ratios): array;
}
