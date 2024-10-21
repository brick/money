<?php

declare(strict_types=1);

namespace Brick\Money\Tests\Context;

use Brick\Math\BigNumber;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\CustomContext;
use Brick\Money\IsoCurrency;
use Brick\Money\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class CustomContext.
 */
class CustomContextTest extends AbstractTestCase
{
    #[DataProvider('providerApplyTo')]
    public function testApplyTo(int $scale, int $step, string $amount, string $currency, RoundingMode $roundingMode, string $expected) : void
    {
        $amount = BigNumber::of($amount);
        $currency = IsoCurrency::of($currency);

        $context = new CustomContext($scale, $step);

        if ($this->isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $context->applyTo($amount, $currency, $roundingMode);

        if (! $this->isExceptionClass($expected)) {
            $this->assertBigDecimalIs($expected, $actual);
        }
    }

    public static function providerApplyTo() : array
    {
        return [
            [2, 1, '1', 'USD', RoundingMode::UNNECESSARY, '1.00'],
            [2, 1, '1.001', 'USD', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [2, 1, '1.001', 'USD', RoundingMode::DOWN, '1.00'],
            [2, 1, '1.001', 'USD', RoundingMode::UP, '1.01'],
            [4, 1, '1', 'USD', RoundingMode::UNNECESSARY, '1.0000'],
            [4, 1, '1.0001', 'USD', RoundingMode::UNNECESSARY, '1.0001'],
            [4, 1, '1.00005', 'USD', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [4, 1, '1.00005', 'USD', RoundingMode::HALF_DOWN, '1.0000'],
            [4, 1, '1.00005', 'USD', RoundingMode::HALF_UP, '1.0001'],
            [0, 1, '1', 'JPY', RoundingMode::UNNECESSARY, '1'],
            [0, 1, '1.00', 'JPY', RoundingMode::UNNECESSARY, '1'],
            [0, 1, '1.01', 'JPY', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [0, 1, '1.01', 'JPY', RoundingMode::DOWN, '1'],
            [0, 1, '1.01', 'JPY', RoundingMode::UP, '2'],
            [2, 1, '1', 'JPY', RoundingMode::UNNECESSARY, '1.00'],
            [2, 1, '1.00', 'JPY', RoundingMode::UNNECESSARY, '1.00'],
            [2, 1, '1.01', 'JPY', RoundingMode::UNNECESSARY, '1.01'],
            [2, 1, '1.001', 'JPY', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [2, 1, '1.001', 'JPY', RoundingMode::DOWN, '1.00'],
            [2, 1, '1.001', 'JPY', RoundingMode::UP, '1.01'],
            [2, 5, '1', 'CHF', RoundingMode::UNNECESSARY, '1.00'],
            [2, 5, '1.05', 'CHF', RoundingMode::UNNECESSARY, '1.05'],
            [2, 5, '1.07', 'CHF', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [2, 5, '1.07', 'CHF', RoundingMode::DOWN, '1.05'],
            [2, 5, '1.07', 'CHF', RoundingMode::UP, '1.10'],
            [2, 5, '1.075', 'CHF', RoundingMode::HALF_DOWN, '1.05'],
            [2, 5, '1.075', 'CHF', RoundingMode::HALF_UP, '1.10'],
            [4, 5, '1', 'CHF', RoundingMode::UNNECESSARY, '1.0000'],
            [4, 5, '1.05', 'CHF', RoundingMode::UNNECESSARY, '1.0500'],
            [4, 5, '1.0005', 'CHF', RoundingMode::UNNECESSARY, '1.0005'],
            [4, 5, '1.0007', 'CHF', RoundingMode::DOWN, '1.0005'],
            [4, 5, '1.0007', 'CHF', RoundingMode::UP, '1.0010'],
            [2, 100, '-1', 'CZK', RoundingMode::UNNECESSARY, '-1.00'],
            [2, 100, '-1.00', 'CZK', RoundingMode::UNNECESSARY, '-1.00'],
            [2, 100, '-1.5', 'CZK', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [2, 100, '-1.5', 'CZK', RoundingMode::DOWN, '-1.00'],
            [2, 100, '-1.5', 'CZK', RoundingMode::UP, '-2.00'],
            [4, 10000, '-1', 'CZK', RoundingMode::UNNECESSARY, '-1.0000'],
            [4, 10000, '-1.00', 'CZK', RoundingMode::UNNECESSARY, '-1.0000'],
            [4, 10000, '-1.5', 'CZK', RoundingMode::UNNECESSARY, RoundingNecessaryException::class],
            [4, 10000, '-1.5', 'CZK', RoundingMode::DOWN, '-1.0000'],
            [4, 10000, '-1.5', 'CZK', RoundingMode::UP, '-2.0000'],
        ];
    }

    public function testGetScaleGetStep() : void
    {
        $context = new CustomContext(8, 50);
        self::assertSame(8, $context->getScale());
        self::assertSame(50, $context->getStep());
    }
}
