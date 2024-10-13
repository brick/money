<?php

use Brick\Money\Exception\UnknownCurrencyException;

require_once __DIR__ . '/vendor/autoload.php';

class CryptoCurrencyProvider implements \Brick\Money\CurrencyProviderInterface
{

    public function getCurrency(string $currencyCode): \Brick\Money\Currency
    {
        $cryptoCurrency = [
            'BTC' => new \Brick\Money\Currency('BTC', 0, 'Bitcoin', 8 ),
        ];

        return $cryptoCurrency[$currencyCode] ?? throw UnknownCurrencyException::unknownCurrency($currencyCode);
    }

    public function getCurrencyForCountry(string $currencyCode): \Brick\Money\Currency
    {
        throw new UnknownCurrencyException('Crypto Currency don\'t have a country code.');
    }
}

class CryptoCurrency extends \Brick\Money\Currency {
    protected static function getCurrencyProvider(): \Brick\Money\CurrencyProviderInterface
    {
        return new CryptoCurrencyProvider();
    }
}

\Brick\Money\Money::of(2, CryptoCurrency::of('BTC'));
\Brick\Money\Money::of(2, \Brick\Money\Currency::of('EUR'));

class CryptoMoney extends \Brick\Money\Money {
    protected static function getCurrencyProvider(): \Brick\Money\CurrencyProviderInterface
    {
        return new CryptoCurrencyProvider();
    }
}

CryptoMoney::of(2, 'BTC')->plus(CryptoMoney::of(2, 'BTC'));
CryptoMoney::of(2, 'BTC')->plus(\Brick\Money\Money::of(2, \Brick\Money\Currency::of('EUR')));