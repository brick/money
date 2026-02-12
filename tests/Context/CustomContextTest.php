<?php

declare(strict_types=1);

namespace Brick\Money\Tests\Context;

use Brick\Math\BigNumber;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\CustomContext;
use Brick\Money\Currency;
use Brick\Money\Exception\MoneyException;
use Brick\Money\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function sprintf;

/**
 * Tests for class CustomContext.
 */
class CustomContextTest extends AbstractTestCase
{
    #[DataProvider('providerApplyTo')]
    public function testApplyTo(int $scale, int $step, string $amount, string $currency, RoundingMode $roundingMode, string $expected): void
    {
        $amount = BigNumber::of($amount);
        $currency = Currency::of($currency);

        $context = new CustomContext($scale, $step);

        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $context->applyTo($amount, $currency, $roundingMode);

        if (! self::isExceptionClass($expected)) {
            self::assertBigDecimalIs($expected, $actual);
        }
    }

    public static function providerApplyTo(): array
    {
        return [
            [2, 1, '1', 'USD', RoundingMode::Unnecessary, '1.00'],
            [2, 1, '1.001', 'USD', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [2, 1, '1.001', 'USD', RoundingMode::Down, '1.00'],
            [2, 1, '1.001', 'USD', RoundingMode::Up, '1.01'],
            [4, 1, '1', 'USD', RoundingMode::Unnecessary, '1.0000'],
            [4, 1, '1.0001', 'USD', RoundingMode::Unnecessary, '1.0001'],
            [4, 1, '1.00005', 'USD', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [4, 1, '1.00005', 'USD', RoundingMode::HalfDown, '1.0000'],
            [4, 1, '1.00005', 'USD', RoundingMode::HalfUp, '1.0001'],
            [0, 1, '1', 'JPY', RoundingMode::Unnecessary, '1'],
            [0, 1, '1.00', 'JPY', RoundingMode::Unnecessary, '1'],
            [0, 1, '1.01', 'JPY', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [0, 1, '1.01', 'JPY', RoundingMode::Down, '1'],
            [0, 1, '1.01', 'JPY', RoundingMode::Up, '2'],
            [2, 1, '1', 'JPY', RoundingMode::Unnecessary, '1.00'],
            [2, 1, '1.00', 'JPY', RoundingMode::Unnecessary, '1.00'],
            [2, 1, '1.01', 'JPY', RoundingMode::Unnecessary, '1.01'],
            [2, 1, '1.001', 'JPY', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [2, 1, '1.001', 'JPY', RoundingMode::Down, '1.00'],
            [2, 1, '1.001', 'JPY', RoundingMode::Up, '1.01'],
            [2, 5, '1', 'CHF', RoundingMode::Unnecessary, '1.00'],
            [2, 5, '1.05', 'CHF', RoundingMode::Unnecessary, '1.05'],
            [2, 5, '1.07', 'CHF', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [2, 5, '1.07', 'CHF', RoundingMode::Down, '1.05'],
            [2, 5, '1.07', 'CHF', RoundingMode::Up, '1.10'],
            [2, 5, '1.075', 'CHF', RoundingMode::HalfDown, '1.05'],
            [2, 5, '1.075', 'CHF', RoundingMode::HalfUp, '1.10'],
            [4, 5, '1', 'CHF', RoundingMode::Unnecessary, '1.0000'],
            [4, 5, '1.05', 'CHF', RoundingMode::Unnecessary, '1.0500'],
            [4, 5, '1.0005', 'CHF', RoundingMode::Unnecessary, '1.0005'],
            [4, 5, '1.0007', 'CHF', RoundingMode::Down, '1.0005'],
            [4, 5, '1.0007', 'CHF', RoundingMode::Up, '1.0010'],
            [2, 100, '-1', 'CZK', RoundingMode::Unnecessary, '-1.00'],
            [2, 100, '-1.00', 'CZK', RoundingMode::Unnecessary, '-1.00'],
            [2, 100, '-1.5', 'CZK', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [2, 100, '-1.5', 'CZK', RoundingMode::Down, '-1.00'],
            [2, 100, '-1.5', 'CZK', RoundingMode::Up, '-2.00'],
            [4, 10000, '-1', 'CZK', RoundingMode::Unnecessary, '-1.0000'],
            [4, 10000, '-1.00', 'CZK', RoundingMode::Unnecessary, '-1.0000'],
            [4, 10000, '-1.5', 'CZK', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [4, 10000, '-1.5', 'CZK', RoundingMode::Down, '-1.0000'],
            [4, 10000, '-1.5', 'CZK', RoundingMode::Up, '-2.0000'],
        ];
    }

    public function testGetScaleGetStep(): void
    {
        $context = new CustomContext(8, 50);
        self::assertSame(8, $context->getScale());
        self::assertSame(50, $context->getStep());
    }

    #[DataProvider('providerStep')]
    public function testStep(int $scale, int $step, bool $isValid): void
    {
        if (! $isValid) {
            $this->expectException(MoneyException::class);
            $this->expectExceptionMessage(sprintf('Invalid step: %d.', $step));
        }

        $context = new CustomContext($scale, $step);

        if ($isValid) {
            self::assertSame($step, $context->getStep());
        }
    }

    public static function providerStep(): array
    {
        return [
            // scale=0: any positive integer
            [0, -1, false],
            [0, 0, false],
            [0, 1, true],
            [0, 2, true],
            [0, 5, true],
            [0, 10, true],
            [0, 17, true],

            // scale=1: step must divide 10 or be a multiple of 10
            [1, -10, false],
            [1, -1, false],
            [1, 0, false],
            [1, 1, true],
            [1, 2, true],
            [1, 3, false],
            [1, 4, false],
            [1, 5, true],
            [1, 6, false],
            [1, 7, false],
            [1, 10, true],
            [1, 15, false],
            [1, 20, true],
            [1, 30, true],
            [1, 33, false],

            // scale=2: step must divide 100 or be a multiple of 100
            [2, -100, false],
            [2, -10, false],
            [2, -1, false],
            [2, 0, false],
            [2, 1, true],
            [2, 2, true],
            [2, 3, false],
            [2, 4, true],
            [2, 5, true],
            [2, 6, false],
            [2, 7, false],
            [2, 8, false],
            [2, 9, false],
            [2, 10, true],
            [2, 11, false],
            [2, 12, false],
            [2, 16, false],
            [2, 20, true],
            [2, 25, true],
            [2, 50, true],
            [2, 75, false],
            [2, 100, true],
            [2, 150, false],
            [2, 200, true],
            [2, 500, true],
            [2, 1000, true],
            [2, 1500, true],
            [2, 1750, false],

            // scale=3: step must divide 1000 or be a multiple of 1000
            [3, -1000, false],
            [3, -100, false],
            [3, -10, false],
            [3, -1, false],
            [3, 0, false],
            [3, 1, true],
            [3, 2, true],
            [3, 3, false],
            [3, 4, true],
            [3, 5, true],
            [3, 6, false],
            [3, 8, true],
            [3, 16, false],
            [3, 25, true],
            [3, 125, true],
            [3, 200, true],
            [3, 250, true],
            [3, 500, true],
            [3, 750, false],
            [3, 1000, true],
            [3, 1500, false],
            [3, 2000, true],
            [3, 5000, true],
            [3, 5500, false],

            // scale=4: step must divide 10000 or be a multiple of 10000
            [4, -10000, false],
            [4, -1000, false],
            [4, -100, false],
            [4, -10, false],
            [4, -1, false],
            [4, 0, false],
            [4, 1, true],
            [4, 2, true],
            [4, 3, false],
            [4, 4, true],
            [4, 5, true],
            [4, 6, false],
            [4, 8, true],
            [4, 16, true],
            [4, 32, false],
            [4, 625, true],
            [4, 1250, true],
            [4, 2500, true],
            [4, 5000, true],
            [4, 7500, false],
            [4, 10000, true],
            [4, 15000, false],
            [4, 20000, true],
            [4, 50000, true],
            [4, 55000, false],
        ];
    }
}
