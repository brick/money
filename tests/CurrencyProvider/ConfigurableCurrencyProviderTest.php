<?php

namespace Brick\Money\Tests\CurrencyProvider;

use Brick\Money\Currency;
use Brick\Money\CurrencyProvider\ConfigurableCurrencyProvider;
use Brick\Money\Tests\AbstractTestCase;

/**
 * Tests for class ConfigurableCurrencyProvider.
 */
class ConfigurableCurrencyProviderTest extends AbstractTestCase
{
    /**
     * @var Currency
     */
    private static $fooCurrency;

    /**
     * @var Currency
     */
    private static $barCurrency;

    /**
     * @var Currency
     */
    private static $bazCurrency;

    /**
     * @var Currency
     */
    private static $competingFooCurrency;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        self::$fooCurrency = Currency::create('FOO', 1, 'Foo currency', 0);
        self::$barCurrency = Currency::create('BAR', 2, 'Bar currency', 2);
        self::$bazCurrency = Currency::create('BAZ', 3, 'Baz currency', 3);

        self::$competingFooCurrency = Currency::create('FOO', 999, 'A competing foo currency', 2);
    }

    /**
     * @return ConfigurableCurrencyProvider
     */
    public function testEmptyProvider()
    {
        $provider = new ConfigurableCurrencyProvider();
        $this->assertCurrencyProviderContains([], $provider);

        return $provider;
    }

    /**
     * @depends testEmptyProvider
     *
     * @param ConfigurableCurrencyProvider $provider
     *
     * @return ConfigurableCurrencyProvider
     */
    public function testRegisterCurrency(ConfigurableCurrencyProvider $provider)
    {
        $provider->registerCurrency(self::$fooCurrency);

        $this->assertCurrencyProviderContains([
            'FOO' => self::$fooCurrency
        ], $provider);

        return $provider;
    }

    /**
     * @depends testRegisterCurrency
     *
     * @param ConfigurableCurrencyProvider $provider
     *
     * @return ConfigurableCurrencyProvider
     */
    public function testRegisterSecondCurrency(ConfigurableCurrencyProvider $provider)
    {
        $provider->registerCurrency(self::$barCurrency);

        $this->assertCurrencyProviderContains([
                'FOO' => self::$fooCurrency,
                'BAR' => self::$barCurrency
        ], $provider);

        return $provider;
    }

    /**
     * @depends testRegisterSecondCurrency
     *
     * @param ConfigurableCurrencyProvider $provider
     *
     * @return ConfigurableCurrencyProvider
     */
    public function testRegisterCurrencyOverride(ConfigurableCurrencyProvider $provider)
    {
        $provider->registerCurrency(self::$competingFooCurrency);

        $this->assertCurrencyProviderContains([
            'FOO' => self::$competingFooCurrency,
            'BAR' => self::$barCurrency
        ], $provider);

        return $provider;
    }

    /**
     * @depends testRegisterCurrencyOverride
     *
     * @param ConfigurableCurrencyProvider $provider
     *
     * @return ConfigurableCurrencyProvider
     */
    public function testRemoveCurrency(ConfigurableCurrencyProvider $provider)
    {
        $provider->removeCurrency(self::$fooCurrency);

        $this->assertCurrencyProviderContains([
            'BAR' => self::$barCurrency
        ], $provider);

        return $provider;
    }

    /**
     * @depends testRemoveCurrency
     * @expectedException \Brick\Money\Exception\UnknownCurrencyException
     *
     * @param ConfigurableCurrencyProvider $provider
     */
    public function testGetUnknownCurrency(ConfigurableCurrencyProvider $provider)
    {
        $provider->getCurrency('XXX');
    }
}
