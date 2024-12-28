# Brick\Money

<img src="https://raw.githubusercontent.com/brick/brick/master/logo.png" alt="" align="left" height="64">

A money and currency library for PHP.

[![Build Status](https://github.com/brick/money/workflows/CI/badge.svg)](https://github.com/brick/money/actions)
[![Coverage Status](https://coveralls.io/repos/github/brick/money/badge.svg?branch=master)](https://coveralls.io/github/brick/money?branch=master)
[![Latest Stable Version](https://poser.pugx.org/brick/money/v/stable)](https://packagist.org/packages/brick/money)
[![Total Downloads](https://poser.pugx.org/brick/money/downloads)](https://packagist.org/packages/brick/money)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](http://opensource.org/licenses/MIT)

## Introduction

Working with financial data is a serious matter, and small rounding mistakes in an application may lead to serious consequences in real life. That's why floating-point arithmetic is not suited for monetary calculations.

This library is based on [brick/math](https://github.com/brick/math) and handles exact calculations on monies of any size.

### Installation

This library is installable via [Composer](https://getcomposer.org/):

```bash
composer require brick/money
```

### Requirements

This library requires PHP 8.1 or later.

For PHP 8.0 compatibility, you can use version `0.8`. For PHP 7.4, you can use version `0.7`. For PHP 7.1, 7.2 & 7.3, you can use version `0.5`. Note that [these PHP versions are EOL](http://php.net/supported-versions.php) and not supported anymore. If you're still using one of these PHP versions, you should consider upgrading as soon as possible.

Although not required, it is recommended that you **install the [GMP](http://php.net/manual/en/book.gmp.php) or [BCMath](http://php.net/manual/en/book.bc.php) extension** to speed up calculations.

### Project status & release process

While this library is still under development, it is well tested and should be stable enough to use in production environments.

The current releases are numbered `0.x.y`. When a non-breaking change is introduced (adding new methods, optimizing existing code, etc.), `y` is incremented.

**When a breaking change is introduced, a new `0.x` version cycle is always started.**

It is therefore safe to lock your project to a given release cycle, such as `0.10.*`.

If you need to upgrade to a newer release cycle, check the [release history](https://github.com/brick/money/releases) for a list of changes introduced by each further `0.x.0` version.

## Creating a Money

### From a regular currency value

To create a Money, call the `of()` factory method:

```php
use Brick\Money\Money;

$money = Money::of(50, 'USD'); // USD 50.00
$money = Money::of('19.9', 'USD'); // USD 19.90
```

If the given amount does not fit in the currency's default number of decimal places (2 for `USD`), you can pass a `RoundingMode`:

```php
$money = Money::of('123.456', 'USD'); // RoundingNecessaryException
$money = Money::of('123.456', 'USD', roundingMode: RoundingMode::UP); // USD 123.46
```

**Note that the rounding mode is only used once**, for the value provided in `of()`; it is not stored in the `Money` object, and any subsequent operation will still need to be passed a `RoundingMode` when necessary.

### From minor units (cents)

Alternatively, you can create a Money from a number of "minor units" (cents), using the `ofMinor()` method:

```php
use Brick\Money\Money;

$money = Money::ofMinor(1234, 'USD'); // USD 12.34
```

## Basic operations

Money is an immutable class: its value never changes, so it can be safely passed around. All operations on a Money therefore return a new instance:

```php
use Brick\Money\Money;

$money = Money::of(50, 'USD');

echo $money->plus('4.99'); // USD 54.99
echo $money->minus(1); // USD 49.00
echo $money->multipliedBy('1.999'); // USD 99.95
echo $money->dividedBy(4); // USD 12.50
```

You can add and subtract Money instances as well:

```php
use Brick\Money\Money;

$cost = Money::of(25, 'USD');
$shipping = Money::of('4.99', 'USD');
$discount = Money::of('2.50', 'USD');

echo $cost->plus($shipping)->minus($discount); // USD 27.49
```

If the two Money instances are not of the same currency, an exception is thrown:

```php
use Brick\Money\Money;

$a = Money::of(1, 'USD');
$b = Money::of(1, 'EUR');

$a->plus($b); // MoneyMismatchException
```

If the result needs rounding, a [rounding mode](https://github.com/brick/math/blob/0.12.0/src/RoundingMode.php) must be passed as second parameter, or an exception is thrown:

```php
use Brick\Money\Money;
use Brick\Math\RoundingMode;

$money = Money::of(50, 'USD');

$money->plus('0.999'); // RoundingNecessaryException
$money->plus('0.999', RoundingMode::DOWN); // USD 50.99

$money->minus('0.999'); // RoundingNecessaryException
$money->minus('0.999', RoundingMode::UP); // USD 49.01

$money->multipliedBy('1.2345'); // RoundingNecessaryException
$money->multipliedBy('1.2345', RoundingMode::DOWN); // USD 61.72

$money->dividedBy(3); // RoundingNecessaryException
$money->dividedBy(3, RoundingMode::UP); // USD 16.67
```

## Money contexts

By default, monies have the official scale for the currency, as defined by the [ISO 4217 standard](https://www.currency-iso.org/) (for example, EUR and USD have 2 decimal places, while JPY has 0) and increment by steps of 1 minor unit (cent); they internally use what is called the `DefaultContext`. You can change this behaviour by providing a `Context` instance. All operations on Money return another Money with the same context. Each context targets a particular use case:

### Cash rounding

Some currencies do not allow the same increments for cash and cashless payments. For example, `CHF` (Swiss Franc) has 2 fraction digits and allows increments of 0.01 CHF, but Switzerland does not have coins of less than 5 cents, or 0.05 CHF.

You can deal with such monies using `CashContext`:

```php
use Brick\Money\Money;
use Brick\Money\Context\CashContext;
use Brick\Math\RoundingMode;

$money = Money::of(10, 'CHF', new CashContext(step: 5)); // CHF 10.00
$money->dividedBy(3, RoundingMode::DOWN); // CHF 3.30
$money->dividedBy(3, RoundingMode::UP); // CHF 3.35
```

### Custom scale

You can use custom scale monies by providing a `CustomContext`:

```php
use Brick\Money\Money;
use Brick\Money\Context\CustomContext;
use Brick\Math\RoundingMode;

$money = Money::of(10, 'USD', new CustomContext(scale: 4)); // USD 10.0000
$money->dividedBy(7, RoundingMode::UP); // USD 1.4286
```

### Auto scale

If you need monies that adjust their scale to fit the operation result, then `AutoContext` is for you:

```php
use Brick\Money\Money;
use Brick\Money\Context\AutoContext;

$money = Money::of('1.10', 'USD', new AutoContext()); // USD 1.1
$money->multipliedBy('2.5'); // USD 2.75
$money->dividedBy(8); // USD 0.1375
```

Note that it is not advised to use `AutoContext` to represent an intermediate calculation result: in particular, it cannot represent the result of all divisions, as some of them may lead to an infinite repeating decimal, which would throw an exception. For these use cases, `RationalMoney` is what you need. Head on to the next section!

## Advanced calculations

You may occasionally need to chain several operations on a Money, and only apply a rounding mode on the very last step; if you applied a rounding mode on every single operation, you might end up with a different result. This is where `RationalMoney` comes into play. This class internally stores the amount as a rational number (a fraction). You can create a `RationalMoney` from a `Money`, and conversely:

```php
use Brick\Money\Money;
use Brick\Math\RoundingMode;

$money = Money::of('9.5', 'EUR') // EUR 9.50
  ->toRational() // EUR 950/100
  ->dividedBy(3) // EUR 950/300
  ->plus('17.795') // EUR 6288500/300000
  ->multipliedBy('1.196') // EUR 7521046000/300000000
  ->to($money->getContext(), RoundingMode::DOWN) // EUR 25.07
```

As you can see, the intermediate results are represented as fractions, and no rounding is ever performed. The final `to()` method converts it to a `Money`, applying a context and a rounding mode if necessary. Most of the time you want the result in the same context as the original Money, which is what the example above does. But you can really apply any context:

```php
...
  ->to(new CustomContext(scale: 8), RoundingMode::UP); // EUR 25.07015334
```

Note: as you can see in the example above, the numbers in the fractions can quickly get very large.
This is usually not a problem—there is no hard limit on the number of digits involved in the calculations—but if necessary,
you can simplify the fraction at any time, without affecting the actual monetary value:

```php
...
  ->multipliedBy('1.196') // EUR 7521046000/300000000
  ->simplified() // EUR 3760523/150000
```

## Money allocation

You can easily split a Money into a number of parts:

```php
use Brick\Money\Money;

$money = Money::of(100, 'USD');
[$a, $b, $c] = $money->split(3); // USD 33.34, USD 33.33, USD 33.33
```

You can also allocate a Money according to a list of ratios. Say you want to distribute a profit of 987.65 CHF to 3 shareholders, having shares of `48%`, `41%` and `11%` of a company:

```php
use Brick\Money\Money;

$profit = Money::of('987.65', 'CHF');
[$a, $b, $c] = $profit->allocate(48, 41, 11); // CHF 474.08, CHF 404.93, CHF 108.64
```

It plays well with cash roundings, too:

```php
use Brick\Money\Money;
use Brick\Money\Context\CashContext;

$profit = Money::of('987.65', 'CHF', new CashContext(step: 5));
[$a, $b, $c] = $profit->allocate(48, 41, 11); // CHF 474.10, CHF 404.95, CHF 108.60
```

Note that the ratios can be any (non-negative) integer values and *do not need to add up to 100*.

When the allocation yields a remainder, both `split()` and `allocate()` spread it on the first monies in the list, until the total adds up to the original Money. This is the algorithm suggested by Martin Fowler in his book [Patterns of Enterprise Application Architecture](https://martinfowler.com/books/eaa.html). You can see that in the first example, where the first money gets `33.34` dollars while the others get `33.33` dollars.

## Money bags (mixed currencies)

You may sometimes need to add monies in different currencies together. `MoneyBag` comes in handy for this:

```php
use Brick\Money\Money;
use Brick\Money\MoneyBag;

$eur = Money::of('12.34', 'EUR');
$jpy = Money::of(123, 'JPY');

$moneyBag = new MoneyBag();
$moneyBag->add($eur);
$moneyBag->add($jpy);
```

You can add any kind of money to a MoneyBag: a `Money`, a `RationalMoney`, or even another `MoneyBag`.

Note that unlike other classes, **`MoneyBag` is mutable**: its value changes when you call `add()` or `subtract()`.

What can you do with a MoneyBag? Well, you can convert it to a Money in the currency of your choice, using a `CurrencyConverter`. Keep reading!

## Currency conversion

This library ships with a `CurrencyConverter` that can convert any kind of money (`Money`, `RationalMoney` or `MoneyBag`) to a Money in another currency:

```php
use Brick\Money\CurrencyConverter;

$exchangeRateProvider = ...;
$converter = new CurrencyConverter($exchangeRateProvider); // optionally provide a Context here

$money = Money::of('50', 'USD');
$converter->convert($money, 'EUR', roundingMode: RoundingMode::DOWN);
```

The converter performs the most precise calculation possible, internally representing the result as a rational number until the very last step.

To use the currency converter, you need an `ExchangeRateProvider`. Several implementations are provided, among which:

### ConfigurableProvider 

This provider starts with a blank state, and allows you to add exchange rates manually:

```php
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;

$provider = new ConfigurableProvider();
$provider->setExchangeRate('EUR', 'USD', '1.0987');
$provider->setExchangeRate('USD', 'EUR', '0.9123');
```

### PDOProvider

This provider reads exchange rates from a database table:

```php
use Brick\Money\ExchangeRateProvider\PDOProvider;
use Brick\Money\ExchangeRateProvider\PDOProviderConfiguration;

$pdo = new \PDO(...);

$configuration = new PDOProviderConfiguration(
    tableName: 'exchange_rates',
    exchangeRateColumnName: 'exchange_rate',
    sourceCurrencyColumnName: 'source_currency_code',
    targetCurrencyColumnName: 'target_currency_code',
);

$provider = new PDOProvider($pdo, $configuration);
```

PDOProvider also supports fixed source or target currency, and dynamic `WHERE` conditions. Check the [PDOProviderConfiguration](https://github.com/brick/money/blob/0.10.0/src/ExchangeRateProvider/PDOProviderConfiguration.php) class for more information.

### BaseCurrencyProvider

This provider builds on top of another exchange rate provider, for the quite common case where all your available exchange rates are relative to a single currency. For example, the [exchange rates](https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml) provided by the European Central Bank are all relative to EUR. You can use them directly to convert EUR to USD, but not USD to EUR, let alone USD to GBP.

This provider will combine exchange rates to get the expected result:

```php
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\ExchangeRateProvider\BaseCurrencyProvider;

$provider = new ConfigurableProvider();
$provider->setExchangeRate('EUR', 'USD', '1.1');
$provider->setExchangeRate('EUR', 'GBP', '0.9');

$provider = new BaseCurrencyProvider($provider, 'EUR');
$provider->getExchangeRate('EUR', 'USD'); // 1.1
$provider->getExchangeRate('USD', 'EUR'); // 10/11
$provider->getExchangeRate('GBP', 'USD'); // 11/9
```

Notice that exchange rate providers can return rational numbers!

### Write your own provider

Writing your own provider is easy: the `ExchangeRateProvider` interface has just one method, `getExchangeRate()`, that takes the currency codes and returns a number.

## Custom currencies

Money supports ISO 4217 currencies by default. You can also use custom currencies by creating a `Currency` instance. Let's create a Bitcoin currency:

```php
use Brick\Money\Currency;
use Brick\Money\Money;

$bitcoin = new Currency(
    'XBT',     // currency code
    0,         // numeric currency code, useful when storing monies in a database; set to 0 if unused
    'Bitcoin', // currency name
    8          // default scale
);
```

You can now use this Currency instead of a currency code:

```php
$money = Money::of('0.123', $bitcoin); // XBT 0.12300000
```

## Formatting

**Formatting requires the [intl extension](http://php.net/manual/en/book.intl.php).**

Money objects can be formatted according to a given locale:

```php
$money = Money::of(5000, 'USD');
echo $money->formatTo('en_US'); // $5,000.00
echo $money->formatTo('fr_FR'); // 5 000,00 $US
```

Alternatively, you can format Money objects with your own instance of [NumberFormatter](http://php.net/manual/en/class.numberformatter.php), which gives you room for customization:

```php
$formatter = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
$formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, 'US$');
$formatter->setSymbol(\NumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL, '·');
$formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 2);

$money = Money::of(5000, 'USD');
echo $money->formatWith($formatter); // US$5·000.00
```

*Important note: because formatting is performed using `NumberFormatter`, the amount is converted to floating point in the process; so discrepancies can appear when formatting very large monetary values.*

## Storing Money objects in the database

### Persisting the amount

- **As an integer**: in many applications, monies are only ever used with their default scale (e.g. 2 decimal places for `USD`, 0 for `JPY`). In this case, the best practice is to store minor units (cents) as an integer field:
  
  ```php
  $integerAmount = $money->getMinorAmount()->toInt();
  ```
  
  And later retrieve it as:
  
  ```php
  Money::ofMinor($integerAmount, $currencyCode);
  ```
  
  This approach works well with all currencies, without having to worry about the scale. You only have to worry about not overflowing an integer (which would throw an exception), but this is unlikely to happen unless you're dealing with huge amounts of money.
  
- **As a decimal**: for most other cases, storing the amount string as a decimal type is advised:
  
  ```php
  $decimalAmount = (string) $money->getAmount();
  ```
  
  And later retrieve it as:
  
  ```php
  Money::of($decimalAmount, $currencyCode);
  ```

### Persisting the currency

- **As a string**: if you only deal with ISO currencies, or custom currencies having a 3-letter currency code, you can store the currency in a `CHAR(3)`. Otherwise, you'll most likely need a `VARCHAR`. You may also use an `ENUM` if your application uses a fixed list of currencies.
  
  ```php
  $currencyCode = $money->getCurrency()->getCurrencyCode();
  ```
  
  When retrieving the currency: you can use ISO currency codes directly in `Money::of()` and `Money::ofMinor()`. For custom currencies, you'll need to convert them to `Currency` instances first.
  
- **As an integer**: if you only deal with ISO currencies, or custom currencies with a numeric code, you may store the currency code as an integer:
  
  ```php
  $numericCode = $money->getCurrency()->getNumericCode();
  ```

  When retrieving the currency: you can use numeric codes of ISO currencies directly in `Money::of()` and `Money::ofMinor()`. For custom currencies, you'll need to convert them to `Currency` instances first.
  
- **Hardcoded**: if your application only ever deals with one currency, you may very well hardcode the currency code and not store it in your database at all.

### Using an ORM

If you're using an ORM such as Doctrine, it is advised to store the amount and currency separately, and perform conversion in the getters/setters:

  ```php
  class Entity
  {
      private int $price;
      private string $currencyCode;

      public function getPrice() : Money
      {
          return Money::ofMinor($this->price, $this->currencyCode);
      }

      public function setPrice(Money $price) : void
      {
          $this->price = $price->getMinorAmount()->toInt();
          $this->currencyCode = $price->getCurrency()->getCurrencyCode();
      }
  }
  ```

## FAQ

> How does this project compare with [moneyphp/money](https://github.com/moneyphp/money)?

Please see [this discussion](https://github.com/brick/money/issues/28).
