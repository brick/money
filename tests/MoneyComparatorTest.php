<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Math\BigNumber;
use Brick\Money\ComparisonMode;
use Brick\Money\ComparisonMode\BaseCurrencyMode;
use Brick\Money\ComparisonMode\PairwiseMode;
use Brick\Money\Context\AutoContext;
use Brick\Money\Currency;
use Brick\Money\Exception\ExchangeRateNotFoundException;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Money;
use Brick\Money\MoneyComparator;
use Brick\Money\RationalMoney;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_map;

/**
 * Tests for class MoneyComparator.
 */
class MoneyComparatorTest extends AbstractTestCase
{
    /**
     * @param ComparisonMode $mode The comparison mode.
     * @param array          $a    The money to compare.
     * @param array          $b    The money to compare to.
     * @param int|string     $cmp  The expected comparison value, or an exception class.
     */
    #[DataProvider('providerCompare')]
    public function testCompare(ComparisonMode $mode, array $a, array $b, int|string $cmp): void
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider(), $mode);

        $a = Money::of(...$a);
        $b = Money::of(...$b);

        if (self::isExceptionClass($cmp)) {
            $this->expectException($cmp);
        }

        self::assertSame($cmp, $comparator->compare($a, $b));
        self::assertSame($cmp < 0, $comparator->isLess($a, $b));
        self::assertSame($cmp > 0, $comparator->isGreater($a, $b));
        self::assertSame($cmp <= 0, $comparator->isLessOrEqual($a, $b));
        self::assertSame($cmp >= 0, $comparator->isGreaterOrEqual($a, $b));
        self::assertSame($cmp === 0, $comparator->isEqual($a, $b));

        if ($cmp <= 0) {
            self::assertSame($a, $comparator->min($a, $b));
        } else {
            self::assertSame($b, $comparator->min($a, $b));
        }

        if ($cmp >= 0) {
            self::assertSame($a, $comparator->max($a, $b));
        } else {
            self::assertSame($b, $comparator->max($a, $b));
        }
    }

    public static function providerCompare(): Generator
    {
        foreach (self::providerComparePairwise() as $data) {
            yield [new PairwiseMode(), ...$data];
        }

        foreach (self::providerCompareWithBaseCurrencyEur() as $data) {
            yield [new BaseCurrencyMode('EUR'), ...$data];
        }
    }

    public static function providerComparePairwise(): array
    {
        return [
            [['1.00', 'EUR'], ['1', 'EUR'], 0],

            [['1.00', 'EUR'], ['1.09', 'USD'], 1],
            [['1.00', 'EUR'], ['1.10', 'USD'], 0],
            [['1.00', 'EUR'], ['1.11', 'USD'], -1],

            [['1.11', 'USD'], ['1.00', 'EUR'], -1],
            [['1.12', 'USD'], ['1.00', 'EUR'], 1],

            [['123.57', 'USD'], ['123.57', 'BSD'], 0],
            [['123.57', 'BSD'], ['123.57', 'USD'], 0],

            [['1000250.123456', 'EUR', new AutoContext()], ['800200.0987648', 'GBP', new AutoContext()], 0],
            [['1000250.123456', 'EUR', new AutoContext()], ['800200.098764', 'GBP', new AutoContext()], 1],
            [['1000250.123456', 'EUR', new AutoContext()], ['800200.098765', 'GBP', new AutoContext()], -1],

            [['800200.098764', 'GBP', new AutoContext()], ['1000250.123456', 'EUR', new AutoContext()], -1],
            [['800200.098764', 'GBP', new AutoContext()], ['960240.1185168000', 'EUR', new AutoContext()], 0],
            [['800200.098764', 'GBP', new AutoContext()], ['960240.118516', 'EUR', new AutoContext()], 1],
            [['800200.098764', 'GBP', new AutoContext()], ['960240.118517', 'EUR', new AutoContext()], -1],

            [['1.0', 'EUR'], ['1.0', 'BSD'], ExchangeRateNotFoundException::class],
        ];
    }

    public static function providerCompareWithBaseCurrencyEur(): array
    {
        return [
            [['1.00', 'EUR'], ['1', 'EUR'], 0],

            [['0.98', 'EUR'], ['1.10', 'USD'], -1],
            [['0.99', 'EUR'], ['1.10', 'USD'], 0],
            [['1.00', 'EUR'], ['1.10', 'USD'], 1],

            [['0.99', 'USD'], ['0.90', 'EUR'], -1],
            [['1.00', 'USD'], ['0.90', 'EUR'], 0],
            [['1.01', 'USD'], ['0.90', 'EUR'], 1],

            [['0.74', 'GBP'], ['1.00', 'USD'], -1],
            [['0.75', 'GBP'], ['1.00', 'USD'], 0],
            [['0.76', 'GBP'], ['1.00', 'USD'], 1],

            [['1.19', 'USD'], ['0.90', 'GBP'], -1],
            [['1.20', 'USD'], ['0.90', 'GBP'], 0],
            [['1.21', 'USD'], ['0.90', 'GBP'], 1],

            [['1.0', 'EUR'], ['1.0', 'BSD'], ExchangeRateNotFoundException::class],
        ];
    }

    /**
     * @param array  $monies      The monies to compare.
     * @param string $expectedMin The expected minimum money, or an exception class.
     */
    #[DataProvider('providerMin')]
    public function testMin(array $monies, string $expectedMin): void
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider(), new PairwiseMode());

        $monies = array_map(
            fn (array $money) => Money::of(...$money),
            $monies,
        );

        if (self::isExceptionClass($expectedMin)) {
            $this->expectException($expectedMin);
        }

        $actualMin = $comparator->min(...$monies);

        if (! self::isExceptionClass($expectedMin)) {
            self::assertMoneyIs($expectedMin, $actualMin);
        }
    }

    public static function providerMin(): array
    {
        return [
            [[['1.00', 'EUR'], ['1.09', 'USD']], 'USD 1.09'],
            [[['1.00', 'EUR'], ['1.10', 'USD']], 'EUR 1.00'],
            [[['1.00', 'EUR'], ['1.11', 'USD']], 'EUR 1.00'],
            [[['1.00', 'EUR'], ['1.09', 'USD'], ['1.20', 'BSD']], 'USD 1.09'],
            [[['1.00', 'EUR'], ['1.12', 'USD'], ['1.20', 'BSD']], ExchangeRateNotFoundException::class],
            [[['1.05', 'EUR'], ['1.00', 'GBP'], ['1.19', 'EUR']], 'EUR 1.05'],
        ];
    }

    /**
     * @param array  $monies      The monies to compare.
     * @param string $expectedMin The expected maximum money, or an exception class.
     */
    #[DataProvider('providerMax')]
    public function testMax(array $monies, string $expectedMin): void
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider(), new PairwiseMode());

        $monies = array_map(
            fn (array $money) => Money::of(...$money),
            $monies,
        );

        if (self::isExceptionClass($expectedMin)) {
            $this->expectException($expectedMin);
        }

        $actualMin = $comparator->max(...$monies);

        if (! self::isExceptionClass($expectedMin)) {
            self::assertMoneyIs($expectedMin, $actualMin);
        }
    }

    public static function providerMax(): array
    {
        return [
            [[['1.00', 'EUR'], ['1.09', 'USD']], 'EUR 1.00'],
            [[['1.00', 'EUR'], ['1.10', 'USD']], 'EUR 1.00'],
            [[['1.00', 'EUR'], ['1.11', 'USD']], 'USD 1.11'],
            [[['1.00', 'EUR'], ['1.09', 'USD'], ['1.20', 'BSD']], ExchangeRateNotFoundException::class],
            [[['1.00', 'EUR'], ['1.22', 'USD'], ['1.20', 'BSD']], 'USD 1.22'],
            [[['1.00', 'EUR'], ['1.12', 'USD'], ['1.20', 'BSD']], 'BSD 1.20'],
            [[['1.05', 'EUR'], ['1.00', 'GBP'], ['1.19', 'EUR']], 'GBP 1.00'],
            [[['1.05', 'EUR'], ['1.00', 'GBP'], ['1.2001', 'EUR', new AutoContext()]], 'EUR 1.2001'],
        ];
    }

    #[DataProvider('providerCompareWithDimensions')]
    public function testCompareWithDimensions(array $rationalMoney, array $money, array $dimensions, int $expected): void
    {
        $provider = $this->getExchangeRateProviderWithDimensions();

        $a = RationalMoney::of(...$rationalMoney);
        $b = Money::of(...$money);

        $withDimensions = new MoneyComparator($provider, new PairwiseMode(), $dimensions);

        self::assertSame($expected, $withDimensions->compare($a, $b));
    }

    public static function providerCompareWithDimensions(): array
    {
        return [
            [['0.99', 'EUR'], ['1.10', 'USD'], ['rateType' => 'spot'], -1],
            [['1.00', 'EUR'], ['1.10', 'USD'], ['rateType' => 'spot'], 0],
            [['1.01', 'EUR'], ['1.10', 'USD'], ['rateType' => 'spot'], 1],
            [['1.10', 'USD'], ['1.00', 'EUR'], ['rateType' => 'spot'], -1],
            [['10/9', 'USD'], ['1.00', 'EUR'], ['rateType' => 'spot'], 0],
            [['1.12', 'USD'], ['1.00', 'EUR'], ['rateType' => 'spot'], 1],
            [['0.99', 'EUR'], ['1.20', 'USD'], ['rateType' => 'forward'], -1],
            [['1.00', 'EUR'], ['1.20', 'USD'], ['rateType' => 'forward'], 0],
            [['1.01', 'EUR'], ['1.20', 'USD'], ['rateType' => 'forward'], 1],
            [['1.24', 'USD'], ['1.00', 'EUR'], ['rateType' => 'forward'], -1],
            [['1.25', 'USD'], ['1.00', 'EUR'], ['rateType' => 'forward'], 0],
            [['1.26', 'USD'], ['1.00', 'EUR'], ['rateType' => 'forward'], 1],
        ];
    }

    public function testCompareWithDimensionsThrowsOnExchangeRateNotFound(): void
    {
        $provider = $this->getExchangeRateProviderWithDimensions();

        $a = Money::of('1.00', 'EUR');
        $b = Money::of('1.10', 'USD');

        $withoutDimensions = new MoneyComparator($provider, new PairwiseMode());
        $this->expectException(ExchangeRateNotFoundException::class);
        $withoutDimensions->compare($a, $b);
    }

    private function getExchangeRateProvider(): ConfigurableProvider
    {
        return ConfigurableProvider::builder()
            ->addExchangeRate('EUR', 'USD', '1.1')
            ->addExchangeRate('USD', 'EUR', '0.9')
            ->addExchangeRate('USD', 'BSD', 1)
            ->addExchangeRate('BSD', 'USD', 1)
            ->addExchangeRate('EUR', 'GBP', '0.8')
            ->addExchangeRate('GBP', 'EUR', '1.2')
            ->build();
    }

    private function getExchangeRateProviderWithDimensions(): ExchangeRateProvider
    {
        return new class() implements ExchangeRateProvider {
            public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber
            {
                $rates = [
                    ['EUR', 'USD', 'spot', '1.1'],
                    ['EUR', 'USD', 'forward', '1.2'],
                    ['USD', 'EUR', 'spot', '0.9'],
                    ['USD', 'EUR', 'forward', '0.8'],
                ];

                foreach ($rates as [$sourceCurrencyCode, $targetCurrencyCode, $rateType, $rate]) {
                    if ($sourceCurrency->getCurrencyCode() !== $sourceCurrencyCode) {
                        continue;
                    }

                    if ($targetCurrency->getCurrencyCode() !== $targetCurrencyCode) {
                        continue;
                    }

                    if (($dimensions['rateType'] ?? null) !== $rateType) {
                        continue;
                    }

                    return BigNumber::of($rate);
                }

                return null;
            }
        };
    }
}
