Brick\Money
===========

<img src="https://raw.githubusercontent.com/brick/brick/master/logo.png" alt="" align="left" height="64">

A money and currency library for PHP.

[![Build Status](https://secure.travis-ci.org/brick/money.svg?branch=master)](http://travis-ci.org/brick/money)
[![Coverage Status](https://coveralls.io/repos/brick/money/badge.svg?branch=master)](https://coveralls.io/r/brick/money?branch=master)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](http://opensource.org/licenses/MIT)

Introduction
------------

Working with financial data is a serious matter, and small rounding mistakes in an application may lead to disastrous
consequences in real life. That's why floating-point arithmetic is not suited for monetary calculations.

This component is based on the [Math](https://github.com/brick/math) component and handles exact calculations on monies of any size.

Requirements and installation
-----------------------------

This library requires PHP 5.6, PHP 7 or [HHVM](http://hhvm.com/).

We recommend installing it with [Composer](https://getcomposer.org/).
Just define the following requirement in your `composer.json` file:

    {
        "require": {
            "brick/money": "dev-master"
        }
    }
