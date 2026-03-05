<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\Exception\ExchangeRateProviderException;
use Brick\Money\ExchangeRateProvider\PdoProvider;
use Brick\Money\ExchangeRateProvider\PdoProviderConfiguration;
use Brick\Money\ExchangeRateProvider\SqlCondition;
use Brick\Money\Tests\AbstractTestCase;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

/**
 * Tests for class PdoProvider.
 */
#[RequiresPhpExtension('pdo_sqlite')]
class PdoProviderTest extends AbstractTestCase
{
    public function testConfigurationBuilderForCurrencyPair(): void
    {
        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->setStaticCondition(new SqlCondition('year = ?', 2025))
            ->build();

        self::assertSame('exchange_rates', $configuration->tableName);
        self::assertSame('exchange_rate', $configuration->exchangeRateColumnName);
        self::assertNull($configuration->sourceCurrencyCode);
        self::assertSame('source_currency', $configuration->sourceCurrencyColumnName);
        self::assertNull($configuration->targetCurrencyCode);
        self::assertSame('target_currency', $configuration->targetCurrencyColumnName);
        self::assertSame('year = ?', $configuration->staticCondition?->getSql());
        self::assertSame([2025], $configuration->staticCondition?->getParameters());
        self::assertSame([], $configuration->dimensionBindings);
    }

    public function testConfigurationBuilderForFixedSourceCurrency(): void
    {
        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setFixedSourceCurrency('EUR')
            ->setTargetCurrencyColumn('target_currency')
            ->setStaticCondition(new SqlCondition('year = ?'))
            ->build();

        self::assertSame('exchange_rates', $configuration->tableName);
        self::assertSame('exchange_rate', $configuration->exchangeRateColumnName);
        self::assertSame('EUR', $configuration->sourceCurrencyCode);
        self::assertNull($configuration->sourceCurrencyColumnName);
        self::assertNull($configuration->targetCurrencyCode);
        self::assertSame('target_currency', $configuration->targetCurrencyColumnName);
        self::assertSame('year = ?', $configuration->staticCondition?->getSql());
        self::assertSame([], $configuration->staticCondition?->getParameters());
        self::assertSame([], $configuration->dimensionBindings);
    }

    public function testConfigurationBuilderForFixedTargetCurrency(): void
    {
        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setFixedTargetCurrency('EUR')
            ->setStaticCondition(new SqlCondition('year = ?'))
            ->build();

        self::assertSame('exchange_rates', $configuration->tableName);
        self::assertSame('exchange_rate', $configuration->exchangeRateColumnName);
        self::assertNull($configuration->sourceCurrencyCode);
        self::assertSame('source_currency', $configuration->sourceCurrencyColumnName);
        self::assertSame('EUR', $configuration->targetCurrencyCode);
        self::assertNull($configuration->targetCurrencyColumnName);
        self::assertSame('year = ?', $configuration->staticCondition?->getSql());
        self::assertSame([], $configuration->staticCondition?->getParameters());
        self::assertSame([], $configuration->dimensionBindings);
    }

    /**
     * @param string      $sourceCurrencyCode The code of the source currency.
     * @param string      $targetCurrencyCode The code of the target currency.
     * @param string|null $expectedResult     The expected exchange rate, or null if not found.
     */
    #[DataProvider('providerGetExchangeRate')]
    public function testGetExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode, ?string $expectedResult): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rates (
                source_currency TEXT NOT NULL,
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?, ?)');

        $statement->execute(['EUR', 'USD', '1.1']);
        $statement->execute(['USD', 'EUR', '0.9']);
        $statement->execute(['USD', 'CAD', '1.2']);

        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->build();

        $provider = new PdoProvider($pdo, $configuration);

        $sourceCurrency = Currency::of($sourceCurrencyCode);
        $targetCurrency = Currency::of($targetCurrencyCode);

        $actualRate = $provider->getExchangeRate($sourceCurrency, $targetCurrency);

        if ($expectedResult === null) {
            self::assertNull($actualRate);
        } else {
            self::assertBigNumberEquals($expectedResult, $actualRate);
        }
    }

    public static function providerGetExchangeRate(): array
    {
        return [
            ['USD', 'EUR', '0.9'],
            ['EUR', 'USD', '1.1'],
            ['USD', 'CAD', '1.2'],
            ['CAD', 'USD', null],
            ['EUR', 'CAD', null],
        ];
    }

    /**
     * @param string      $sourceCurrencyCode The code of the source currency.
     * @param string      $targetCurrencyCode The code of the target currency.
     * @param string|null $expectedResult     The expected exchange rate, or null if not found.
     */
    #[DataProvider('providerWithFixedSourceCurrency')]
    public function testWithFixedSourceCurrency(string $sourceCurrencyCode, string $targetCurrencyCode, ?string $expectedResult): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rates (
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?)');

        $statement->execute(['USD', '1.1']);
        $statement->execute(['CAD', '1.2']);

        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setFixedSourceCurrency('EUR')
            ->setTargetCurrencyColumn('target_currency')
            ->build();

        $provider = new PdoProvider($pdo, $configuration);

        $sourceCurrency = Currency::of($sourceCurrencyCode);
        $targetCurrency = Currency::of($targetCurrencyCode);

        $actualRate = $provider->getExchangeRate($sourceCurrency, $targetCurrency);

        if ($expectedResult === null) {
            self::assertNull($actualRate);
        } else {
            self::assertBigNumberEquals($expectedResult, $actualRate);
        }
    }

    public static function providerWithFixedSourceCurrency(): array
    {
        return [
            ['EUR', 'USD', '1.1'],
            ['EUR', 'CAD', '1.2'],
            ['EUR', 'GBP', null],
            ['USD', 'EUR', null],
            ['CAD', 'EUR', null],
        ];
    }

    /**
     * @param string      $sourceCurrencyCode The code of the source currency.
     * @param string      $targetCurrencyCode The code of the target currency.
     * @param string|null $expectedResult     The expected exchange rate, or null if not found.
     */
    #[DataProvider('providerWithFixedTargetCurrency')]
    public function testWithFixedTargetCurrency(string $sourceCurrencyCode, string $targetCurrencyCode, ?string $expectedResult): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rates (
                source_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?)');

        $statement->execute(['USD', '0.9']);
        $statement->execute(['CAD', '0.8']);

        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setFixedTargetCurrency('EUR')
            ->build();

        $provider = new PdoProvider($pdo, $configuration);

        $sourceCurrency = Currency::of($sourceCurrencyCode);
        $targetCurrency = Currency::of($targetCurrencyCode);

        $actualRate = $provider->getExchangeRate($sourceCurrency, $targetCurrency);

        if ($expectedResult === null) {
            self::assertNull($actualRate);
        } else {
            self::assertBigNumberEquals($expectedResult, $actualRate);
        }
    }

    public static function providerWithFixedTargetCurrency(): array
    {
        return [
            ['USD', 'EUR', '0.9'],
            ['CAD', 'EUR', '0.8'],
            ['GBP', 'EUR', null],
            ['EUR', 'USD', null],
            ['EUR', 'CAD', null],
        ];
    }

    /**
     * @param string               $sourceCurrencyCode The code of the source currency.
     * @param string               $targetCurrencyCode The code of the target currency.
     * @param array<string, mixed> $dimensions         The dimensions used to resolve rate lookup.
     * @param string|null          $expectedResult     The expected exchange rate, or null if not found.
     */
    #[DataProvider('providerWithDimensions')]
    public function testWithDimensions(string $sourceCurrencyCode, string $targetCurrencyCode, array $dimensions, ?string $expectedResult): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rates (
                year INTEGER NOT NULL,
                month INTEGER NOT NULL,
                source_currency TEXT NOT NULL,
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?, ?, ?, ?)');

        $statement->execute([2017, 8, 'EUR', 'USD', '1.1']);
        $statement->execute([2017, 8, 'EUR', 'CAD', '1.2']);
        $statement->execute([2017, 9, 'EUR', 'USD', '1.15']);
        $statement->execute([2017, 9, 'EUR', 'CAD', '1.25']);

        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->bindDimension('year', fn ($year) => new SqlCondition('year = ?', $year))
            ->bindDimension('month', fn ($month) => new SqlCondition('month = ?', $month))
            ->bindDimension(
                'as_of',
                fn (DateTimeInterface $date) => new SqlCondition(
                    'year = ? AND month = ?',
                    (int) $date->format('Y'),
                    (int) $date->format('m'),
                ),
            )
            ->build();

        $provider = new PdoProvider($pdo, $configuration);

        $sourceCurrency = Currency::of($sourceCurrencyCode);
        $targetCurrency = Currency::of($targetCurrencyCode);

        $actualRate = $provider->getExchangeRate($sourceCurrency, $targetCurrency, $dimensions);

        if ($expectedResult === null) {
            self::assertNull($actualRate);
        } else {
            self::assertBigNumberEquals($expectedResult, $actualRate);
        }
    }

    public static function providerWithDimensions(): array
    {
        return [
            ['EUR', 'USD', ['year' => 2017, 'month' => 8], '1.1'],
            ['EUR', 'CAD', ['year' => 2017, 'month' => 8], '1.2'],
            ['EUR', 'GBP', ['year' => 2017, 'month' => 8], null],
            ['EUR', 'USD', ['year' => 2017, 'month' => 9], '1.15'],
            ['EUR', 'CAD', ['year' => 2017, 'month' => 9], '1.25'],
            ['EUR', 'GBP', ['year' => 2017, 'month' => 9], null],
            ['EUR', 'USD', ['year' => 2017, 'month' => 10], null],
            ['EUR', 'CAD', ['year' => 2017, 'month' => 10], null],
            ['EUR', 'USD', ['as_of' => new DateTimeImmutable('2017-08-10')], '1.1'],
            ['EUR', 'CAD', ['as_of' => new DateTimeImmutable('2017-09-01')], '1.25'],
        ];
    }

    public function testUnknownDimensionReturnsNull(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (source_currency TEXT, target_currency TEXT, exchange_rate REAL)');

        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->build();

        $provider = new PdoProvider($pdo, $configuration);

        $rate = $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'), ['date' => new DateTimeImmutable('2017-08-01')]);

        self::assertNull($rate);
    }

    public function testDimensionCanResolveToNull(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (flag INTEGER, source_currency TEXT, target_currency TEXT, exchange_rate REAL)');
        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?, ?, ?)');
        $statement->execute([1, 'EUR', 'USD', '1.1']);

        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->bindDimension('flag', fn (bool $flag) => $flag ? new SqlCondition('flag = 1') : null)
            ->build();

        $provider = new PdoProvider($pdo, $configuration);

        $rate = $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'), ['flag' => false]);

        self::assertBigNumberEquals('1.1', $rate);
    }

    public function testDimensionResolverReturningUnsupportedValueTypeThrows(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (source_currency TEXT, target_currency TEXT, exchange_rate REAL)');

        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->bindDimension('year', fn () => new SqlCondition('year = ?', new DateTimeImmutable('2025-01-01')))
            ->build();

        $provider = new PdoProvider($pdo, $configuration);

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage(
            'An exception occurred while resolving SQL condition for dimension: year.',
        );

        $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'), ['year' => 2025]);
    }

    public function testPrepareExceptionIsWrapped(): void
    {
        $pdo = new PDO('sqlite::memory:');

        $configuration = PdoProviderConfiguration::builder('exchange_rates WHERE', 'exchange_rate')
            ->setFixedSourceCurrency('EUR')
            ->setFixedTargetCurrency('USD')
            ->build();

        $provider = new PdoProvider($pdo, $configuration);

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage('Failed to prepare exchange rate query due to a PDO exception.');

        $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));
    }

    public function testStaticConditionsWithStaticParameters(): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rates (
                provider TEXT NOT NULL,
                source_currency TEXT NOT NULL,
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?, ?, ?)');
        $statement->execute(['ECB', 'EUR', 'USD', '1.1']);
        $statement->execute(['INTERNAL', 'EUR', 'USD', '1.12']);

        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->setStaticCondition(new SqlCondition('provider = ?', 'ECB'))
            ->build();

        $provider = new PdoProvider($pdo, $configuration);
        $rate = $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));

        self::assertBigNumberEquals('1.1', $rate);
    }

    public function testFixedPairWithoutConditionsReadsSingleRowTable(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (exchange_rate REAL NOT NULL)');
        $pdo->prepare('INSERT INTO exchange_rates VALUES (?)')->execute(['1.1']);

        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setFixedSourceCurrency('EUR')
            ->setFixedTargetCurrency('USD')
            ->build();

        $provider = new PdoProvider($pdo, $configuration);
        $rate = $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));

        self::assertBigNumberEquals('1.1', $rate);
    }

    public function testThrowsWhenQueryReturnsMoreThanOneRow(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (exchange_rate REAL NOT NULL)');
        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?)');
        $statement->execute(['1.1']);
        $statement->execute(['1.2']);

        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setFixedSourceCurrency('EUR')
            ->setFixedTargetCurrency('USD')
            ->build();

        $provider = new PdoProvider($pdo, $configuration);

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage(
            'Exchange rate lookup matched multiple rows. Configure orderBy() to select one row deterministically if that is intended.',
        );

        $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));
    }

    public function testMultipleRowsMessageMentionsOmittedDimensions(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('
            CREATE TABLE exchange_rates (
                year INTEGER NOT NULL,
                month INTEGER NOT NULL,
                source_currency TEXT NOT NULL,
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?, ?, ?, ?)');
        $statement->execute([2017, 8, 'EUR', 'USD', '1.1']);
        $statement->execute([2017, 9, 'EUR', 'USD', '1.15']);

        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->bindDimension('year', fn (int $year) => new SqlCondition('year = ?', $year))
            ->bindDimension('month', fn (int $month) => new SqlCondition('month = ?', $month))
            ->build();

        $provider = new PdoProvider($pdo, $configuration);

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage(
            'Exchange rate lookup matched multiple rows. Missing dimensions may be required to disambiguate: year, month. Configure orderBy() to select one row deterministically if that is intended.',
        );

        $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));
    }

    public function testOrderByPicksFirstRowDesc(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (priority INTEGER NOT NULL, exchange_rate REAL NOT NULL)');
        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?)');
        $statement->execute([1, '1.1']);
        $statement->execute([2, '1.2']);

        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setFixedSourceCurrency('EUR')
            ->setFixedTargetCurrency('USD')
            ->orderBy('priority', 'DESC')
            ->build();

        $provider = new PdoProvider($pdo, $configuration);
        $rate = $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));

        self::assertBigNumberEquals('1.2', $rate);
    }

    public function testInvalidOrderDirectionThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Order direction must be ASC or DESC.');

        PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->orderBy('priority', 'DOWN');
    }

    public function testThenOrderBySupportsMultipleColumns(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (priority INTEGER NOT NULL, version INTEGER NOT NULL, exchange_rate REAL NOT NULL)');
        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?, ?)');
        $statement->execute([2, 1, '1.2']);
        $statement->execute([2, 3, '1.23']);
        $statement->execute([2, 2, '1.22']);

        $configuration = PdoProviderConfiguration::builder('exchange_rates', 'exchange_rate')
            ->setFixedSourceCurrency('EUR')
            ->setFixedTargetCurrency('USD')
            ->orderBy('priority', 'DESC')
            ->thenOrderBy('version', 'DESC')
            ->build();

        $provider = new PdoProvider($pdo, $configuration);
        $rate = $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));

        self::assertBigNumberEquals('1.23', $rate);
    }
}
