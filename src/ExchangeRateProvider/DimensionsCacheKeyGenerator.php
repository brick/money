<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

/**
 * Generates a cache key for exchange-rate dimensions.
 */
interface DimensionsCacheKeyGenerator
{
    /**
     * @param array<string, mixed> $dimensions The exchange-rate lookup dimensions.
     *
     * @return string|null A cache key, or null when dimensions are not cacheable.
     */
    public function generateCacheKey(array $dimensions): ?string;
}
