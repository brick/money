<?php

namespace Brick\Money\Tests;

use Brick\Money\Currency;

class CustomCurrency implements Currency
{
    public function __toString(): string
    {
        return $this->getCode();
    }

    public function getCode(): string
    {
        return 'CUSTOM';
    }

    public function getDefaultFractionDigits(): int
    {
        return 3;
    }

    public function jsonSerialize(): mixed
    {
        return $this->getCode();
    }

    public function is(Currency $currency): bool
    {
        return $currency->getCode() === $this->getCode();
    }
}