<?php

declare(strict_types=1);

namespace Brick\Money\Allocation;

use Brick\Math\BigInteger;

use function array_values;

/**
 * @internal
 */
abstract class AbstractFloorAbsorbStrategy extends AbstractFloorStrategy
{
    /**
     * @param list<BigInteger> $ratios
     *
     * @return list<BigInteger>
     */
    final public function allocate(BigInteger $amountInSteps, array $ratios): array
    {
        [$floors, $remainderSteps] = $this->computeFloors($amountInSteps, $ratios);

        $indices = $this->priorityIndices($amountInSteps, $ratios);

        for ($i = 0, $r = $remainderSteps->toInt(); $i < $r; $i++) {
            $floors[$indices[$i]] = $floors[$indices[$i]]->plus(1);
        }

        return array_values($floors);
    }

    /**
     * Returns the indices of allocatees in priority order for receiving remainder units.
     *
     * @param list<BigInteger> $ratios
     *
     * @return list<int>
     */
    abstract protected function priorityIndices(BigInteger $amountInSteps, array $ratios): array;
}
