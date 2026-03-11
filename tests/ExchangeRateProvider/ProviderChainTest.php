<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\ExchangeRateProvider\ProviderChain;
use Brick\Money\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Tests for class ProviderChain.
 */
class ProviderChainTest extends AbstractTestCase
{
    private static ExchangeRateProvider $provider1;

    private static ExchangeRateProvider $provider2;

    public static function setUpBeforeClass(): void
    {
        self::$provider1 = ConfigurableProvider::builder()
            ->addExchangeRate('USD', 'GBP', '0.7')
            ->addExchangeRate('USD', 'EUR', '0.9')
            ->build();

        self::$provider2 = ConfigurableProvider::builder()
            ->addExchangeRate('USD', 'EUR', '0.8')
            ->addExchangeRate('EUR', 'USD', '1.2')
            ->build();
    }

    public function testUnknownExchangeRate(): void
    {
        $providerChain = new ProviderChain();

        self::assertNull($providerChain->getExchangeRate(Currency::of('USD'), Currency::of('GBP')));
    }

    public function testAddFirstProvider(): ProviderChain
    {
        $provider = new ProviderChain();
        $provider->addExchangeRateProvider(self::$provider1);

        self::assertBigNumberEquals('0.7', $provider->getExchangeRate(Currency::of('USD'), Currency::of('GBP')));
        self::assertBigNumberEquals('0.9', $provider->getExchangeRate(Currency::of('USD'), Currency::of('EUR')));

        return $provider;
    }

    #[Depends('testAddFirstProvider')]
    public function testAddSecondProvider(ProviderChain $provider): ProviderChain
    {
        $provider->addExchangeRateProvider(self::$provider2);

        self::assertBigNumberEquals('0.7', $provider->getExchangeRate(Currency::of('USD'), Currency::of('GBP')));
        self::assertBigNumberEquals('0.9', $provider->getExchangeRate(Currency::of('USD'), Currency::of('EUR')));
        self::assertBigNumberEquals('1.2', $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD')));

        return $provider;
    }

    #[Depends('testAddSecondProvider')]
    public function testRemoveProvider(ProviderChain $provider): void
    {
        $provider->removeExchangeRateProvider(self::$provider1);

        self::assertBigNumberEquals('0.8', $provider->getExchangeRate(Currency::of('USD'), Currency::of('EUR')));
        self::assertBigNumberEquals('1.2', $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD')));
    }
}
