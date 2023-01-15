# Changelog

## [0.7.1](https://github.com/brick/money/releases/tag/0.7.1) - 2023-01-16

👌 **Improvements**

- Compatibility with `brick/math` version `0.11`

## [0.7.0](https://github.com/brick/money/releases/tag/0.7.0) - 2022-10-06

💥 **Breaking changes**

- JSON extension is now required for PHP 7.4 (always available with PHP >= 8.0)
- `AbstractMoney` is now officially sealed, extending it yourself is not supported

✨ **New features**

- `Money` and `RationalMoney` now implement `JsonSerializable`

## [0.6.0](https://github.com/brick/money/releases/tag/0.6.0) - 2022-08-02

💥 **Breaking changes**

- Minimum PHP version is now 7.4
- `AbstractMoney::getAmount()` now has a return type
- `CurrencyConverter`'s constructor does not accept a default `$context` anymore
- `CurrencyConverter::convert()` now requires the `$context` previously accepted by the constructor as third parameter
- `Money::allocateWithRemainder()` now refuses to allocate a portion of the amount that cannot be spread over all ratios, and instead adds that amount to the remainder (#55)
- `Money::splitWithRemainder()` now behaves like `allocateWithRemainder()`

✨ **New ISO currencies**

- `SLE` (Leone) in Sierra Leone (`SL`)

👌 **Improvements**

- Compatibility with `brick/math` version `0.10`

## [0.5.2](https://github.com/brick/money/releases/tag/0.5.2) - 2021-04-03

✨ **New methods**

- `Money::allocateWithRemainder()`
- `Money::splitWithRemainder()`

These methods perform like their `allocate()` and `split()` counterparts, but append the remainder at the end of the returned array instead of spreading it over the first monies.

Thanks @NCatalani!

## [0.5.1](https://github.com/brick/money/releases/tag/0.5.1) - 2021-02-10

👌 **Improvement**

`BaseCurrencyProvider` now always returns a `BigNumber` for convenience (#37).
This is useful if you're using `BaseCurrencyProvider` on its own, not just in `CurrencyConverter`.

Thanks @rdarcy1!

## [0.5.0](https://github.com/brick/money/releases/tag/0.5.0) - 2020-08-19

👌 **Improvements**

- compatibility with `brick/math` version `0.9`

⚠️ **Caution**

When using `brick/math` version `0.9`, the `Money` factory methods such as `of()` and `ofMinor()` now accept decimal numbers in the form `.123` and `123.`,  and do not throw an exception anymore in this case.

## [0.4.5](https://github.com/brick/money/releases/tag/0.4.5) - 2020-05-31

🐛 **Bug fix**

`MoneyBag::getAmount()`, `add()` and `subtract()` would throw an exception when using a custom currency (#25).

## [0.4.4](https://github.com/brick/money/releases/tag/0.4.4) - 2020-01-23

✨ **New method**

`AbstractMoney::isAmountAndCurrencyEqualTo()` compares a money to another. (#17)

This method is different from `isEqualTo()` in 2 aspects:

- it only accepts another money, not a raw number;
- **it returns `false` if the money is in another currency**, instead of throwing an exception.

## [0.4.3](https://github.com/brick/money/releases/tag/0.4.3) - 2020-01-09

🛠 **Improvements**

- `MoneyBag::getAmount()` now accepts an ISO numeric currency code as well

✨ **New methods**

- `CurrencyConverter::convertToRational()` converts to a `RationalMoney` (#22)

## [0.4.2](https://github.com/brick/money/releases/tag/0.4.2) - 2019-07-04

Performance improvement when calling `Money::formatTo()` many times for the same locale.

## [0.4.1](https://github.com/brick/money/releases/tag/0.4.1) - 2018-10-17

Added support for `brick/math` version `0.8`.

## [0.4.0](https://github.com/brick/money/releases/tag/0.4.0) - 2018-10-09

**Breaking Changes**

- Deprecated method `BigRational::toMoney()` has been removed, use `BigRational::to()` instead;
- `BigRational::__toString()` now always outputs the amount in non-simplified rational form.

**New methods**

- `BigRational::simplified()` returns a copy of the money with the amount simplified.

## [0.3.4](https://github.com/brick/money/releases/tag/0.3.4) - 2018-09-12

ISO currency list update.

## [0.3.3](https://github.com/brick/money/releases/tag/0.3.3) - 2018-08-22

ISO currency list update.

## [0.3.2](https://github.com/brick/money/releases/tag/0.3.2) - 2018-08-20

`Money::formatTo()` can now format the amount as a whole number:

```php
formatTo(string $locale, bool $allowWholeNumber = false) : string
```

By default, `formatTo()` always outputs all the fraction digits:

```php
Money::of('23.5', 'USD')->formatTo('en_US'); // $23.50
Money::of(23, 'USD')->formatTo('en_US'); // $23.00
```

But can now be allowed to return the whole number by passing `true` as a second argument:

```php
Money::of('23.5', 'USD')->formatTo('en_US', true); // $23.50
Money::of(23, 'USD')->formatTo('en_US', true); // $23
```

*Note that this version now requires `brick/math` version `0.7.3`. This is not a BC break. If you've locked your composer.json to an earlier version, you will just not be able to install `brick/money` version `0.3.2`.*

## [0.3.1](https://github.com/brick/money/releases/tag/0.3.1) - 2018-08-04

ISO currency list update.

## [0.3.0](https://github.com/brick/money/releases/tag/0.3.0) - 2018-07-26

**New methods:**

- `CurrencyConversionException::getSourceCurrencyCode()`
- `CurrencyConversionException::getTargetCurrencyCode()`

This allows to programmatically get the failing currency pair when an exchange rate is not available.

**Breaking change:**

- `CurrencyConversionException` constructor signature changed

Although this is technically a breaking change and requires a version bump, your code is unlikely to be affected, unless you're creating `CurrencyConversionException` instances manually (you shouldn't).

## [0.2.4](https://github.com/brick/money/releases/tag/0.2.4) - 2018-01-10

ISO currency list update.

## [0.2.3](https://github.com/brick/money/releases/tag/0.2.3) - 2017-12-01

Bug fix: `Money::allocate()` incorrectly allocated negative monies.

## [0.2.2](https://github.com/brick/money/releases/tag/0.2.2) - 2017-11-20

`Money::formatTo()` now always respects the scale of the Money.

## [0.2.1](https://github.com/brick/money/releases/tag/0.2.1) - 2017-11-05

New method: `CustomContext::getScale()`

## [0.2.0](https://github.com/brick/money/releases/tag/0.2.0) - 2017-10-02

- Minimum requirement is now PHP 7.1
- `BigRational::toMoney()` has been deprecated; use `to()` instead. This is the result of a factorization of a common feature in Money and RationalMoney.

## [0.1.1](https://github.com/brick/money/releases/tag/0.1.1) - 2017-12-08

Backports from 0.2.x:

- New method: `CustomContext::getScale()`
- `Money::formatTo()` now always respects the scale of the Money
- Bug fix: `Money::allocate()` incorrectly allocated negative monies

## [0.1.0](https://github.com/brick/money/releases/tag/0.1.0) - 2017-10-02

First beta release!

