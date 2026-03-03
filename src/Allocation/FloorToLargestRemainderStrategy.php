<?php

declare(strict_types=1);

namespace Brick\Money\Allocation;

use Brick\Math\BigInteger;

use function array_keys;
use function array_map;
use function usort;

/**
 * @internal
 */
final readonly class FloorToLargestRemainderStrategy extends AbstractFloorAbsorbStrategy
{
    protected function prioritizeIndices(BigInteger $amountInSteps, array $ratios): array
    {
        $total = BigInteger::sum(...$ratios);
        $remainders = array_map(
            fn (BigInteger $ratio) => $amountInSteps->multipliedBy($ratio)->remainder($total),
            $ratios,
        );

        $indices = array_keys($ratios);

        usort($indices, fn (int $a, int $b): int => $remainders[$b]->compareTo($remainders[$a]) ?: $a <=> $b);

        return $indices;
    }
}
