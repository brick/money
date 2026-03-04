<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider\PdoProvider;
use Brick\Money\ExchangeRateProvider\PdoProviderConfiguration;
use Brick\Money\Tests\AbstractTestCase;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

/**
 * Tests for class PdoProvider.
 */
#[RequiresPhpExtension('pdo_sqlite')]
class PdoProviderTest extends AbstractTestCase
{
    public function testConfigurationFactoryForCurrencyPair(): void
    {
        $configuration = PdoProviderConfiguration::forCurrencyPair(
            tableName: 'exchange_rates',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyColumnName: 'source_currency',
            targetCurrencyColumnName: 'target_currency',
            whereConditions: 'year = ?',
        );

        self::assertSame('exchange_rates', $configuration->tableName);
        self::assertSame('exchange_rate', $configuration->exchangeRateColumnName);
        self::assertNull($configuration->sourceCurrencyCode);
        self::assertSame('source_currency', $configuration->sourceCurrencyColumnName);
        self::assertNull($configuration->targetCurrencyCode);
        self::assertSame('target_currency', $configuration->targetCurrencyColumnName);
        self::assertSame('year = ?', $configuration->whereConditions);
    }

    public function testConfigurationFactoryForFixedSourceCurrency(): void
    {
        $configuration = PdoProviderConfiguration::forFixedSourceCurrency(
            tableName: 'exchange_rates',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyCode: 'EUR',
            targetCurrencyColumnName: 'target_currency',
            whereConditions: 'year = ?',
        );

        self::assertSame('exchange_rates', $configuration->tableName);
        self::assertSame('exchange_rate', $configuration->exchangeRateColumnName);
        self::assertSame('EUR', $configuration->sourceCurrencyCode);
        self::assertNull($configuration->sourceCurrencyColumnName);
        self::assertNull($configuration->targetCurrencyCode);
        self::assertSame('target_currency', $configuration->targetCurrencyColumnName);
        self::assertSame('year = ?', $configuration->whereConditions);
    }

    public function testConfigurationFactoryForFixedTargetCurrency(): void
    {
        $configuration = PdoProviderConfiguration::forFixedTargetCurrency(
            tableName: 'exchange_rates',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyColumnName: 'source_currency',
            targetCurrencyCode: 'EUR',
            whereConditions: 'year = ?',
        );

        self::assertSame('exchange_rates', $configuration->tableName);
        self::assertSame('exchange_rate', $configuration->exchangeRateColumnName);
        self::assertNull($configuration->sourceCurrencyCode);
        self::assertSame('source_currency', $configuration->sourceCurrencyColumnName);
        self::assertSame('EUR', $configuration->targetCurrencyCode);
        self::assertNull($configuration->targetCurrencyColumnName);
        self::assertSame('year = ?', $configuration->whereConditions);
    }

    /**
     * @param string      $sourceCurrencyCode The code of the source currency.
     * @param string      $targetCurrencyCode The code of the target currency.
     * @param string|null $expectedRate       The expected exchange rate, or null if not found.
     */
    #[DataProvider('providerGetExchangeRate')]
    public function testGetExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode, ?string $expectedRate): void
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

        $configuration = PdoProviderConfiguration::forCurrencyPair(
            tableName: 'exchange_rates',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyColumnName: 'source_currency',
            targetCurrencyColumnName: 'target_currency',
        );

        $provider = new PdoProvider($pdo, $configuration);

        $sourceCurrency = Currency::of($sourceCurrencyCode);
        $targetCurrency = Currency::of($targetCurrencyCode);

        $actualRate = $provider->getExchangeRate($sourceCurrency, $targetCurrency);

        if ($expectedRate === null) {
            self::assertNull($actualRate);
        } else {
            self::assertNotNull($actualRate);
            self::assertBigNumberEquals($expectedRate, $actualRate);
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
     * @param string|null $expectedRate       The expected exchange rate, or null if not found.
     */
    #[DataProvider('providerWithFixedSourceCurrency')]
    public function testWithFixedSourceCurrency(string $sourceCurrencyCode, string $targetCurrencyCode, ?string $expectedRate): void
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

        $configuration = PdoProviderConfiguration::forFixedSourceCurrency(
            tableName: 'exchange_rates',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyCode: 'EUR',
            targetCurrencyColumnName: 'target_currency',
        );

        $provider = new PdoProvider($pdo, $configuration);

        $sourceCurrency = Currency::of($sourceCurrencyCode);
        $targetCurrency = Currency::of($targetCurrencyCode);

        $actualRate = $provider->getExchangeRate($sourceCurrency, $targetCurrency);

        if ($expectedRate === null) {
            self::assertNull($actualRate);
        } else {
            self::assertNotNull($actualRate);
            self::assertBigNumberEquals($expectedRate, $actualRate);
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
     * @param string|null $expectedRate       The expected exchange rate, or null if not found.
     */
    #[DataProvider('providerWithFixedTargetCurrency')]
    public function testWithFixedTargetCurrency(string $sourceCurrencyCode, string $targetCurrencyCode, ?string $expectedRate): void
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

        $configuration = PdoProviderConfiguration::forFixedTargetCurrency(
            tableName: 'exchange_rates',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyColumnName: 'source_currency',
            targetCurrencyCode: 'EUR',
        );

        $provider = new PdoProvider($pdo, $configuration);

        $sourceCurrency = Currency::of($sourceCurrencyCode);
        $targetCurrency = Currency::of($targetCurrencyCode);

        $actualRate = $provider->getExchangeRate($sourceCurrency, $targetCurrency);

        if ($expectedRate === null) {
            self::assertNull($actualRate);
        } else {
            self::assertNotNull($actualRate);
            self::assertBigNumberEquals($expectedRate, $actualRate);
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
     * @param string      $sourceCurrencyCode The code of the source currency.
     * @param string      $targetCurrencyCode The code of the target currency.
     * @param array       $parameters         The parameters to resolve the extra query placeholders.
     * @param string|null $expectedRate       The expected exchange rate, or null if not found.
     */
    #[DataProvider('providerWithParameters')]
    public function testWithParameters(string $sourceCurrencyCode, string $targetCurrencyCode, array $parameters, ?string $expectedRate): void
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

        $configuration = PdoProviderConfiguration::forCurrencyPair(
            tableName: 'exchange_rates',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyColumnName: 'source_currency',
            targetCurrencyColumnName: 'target_currency',
            whereConditions: 'year = ? AND month = ?',
        );

        $provider = new PdoProvider($pdo, $configuration);
        $provider->setParameters(...$parameters);

        $sourceCurrency = Currency::of($sourceCurrencyCode);
        $targetCurrency = Currency::of($targetCurrencyCode);

        $actualRate = $provider->getExchangeRate($sourceCurrency, $targetCurrency);

        if ($expectedRate === null) {
            self::assertNull($actualRate);
        } else {
            self::assertNotNull($actualRate);
            self::assertBigNumberEquals($expectedRate, $actualRate);
        }
    }

    public static function providerWithParameters(): array
    {
        return [
            ['EUR', 'USD', [2017, 8], '1.1'],
            ['EUR', 'CAD', [2017, 8], '1.2'],
            ['EUR', 'GBP', [2017, 8], null],
            ['EUR', 'USD', [2017, 9], '1.15'],
            ['EUR', 'CAD', [2017, 9], '1.25'],
            ['EUR', 'GBP', [2017, 9], null],
            ['EUR', 'USD', [2017, 10], null],
            ['EUR', 'CAD', [2017, 10], null],
        ];
    }
}
