<?php

namespace Brick\Money\Tests\Misc;

use Brick\Money\Context\DefaultContext;
use Brick\Money\Money;

/** @method void extended() Method to test autocompletion in IDE */
class ExtendedMoney extends Money {}

/**
 * @coversDefaultClass \Brick\Money\Tests\Misc\ExtendedMoney
 */
class ExtendabilityTest extends \Brick\Money\Tests\AbstractTestCase
{
	public function testStaticMethods()
	{
		$one = ExtendedMoney::of(1, 'EUR');
		$two = ExtendedMoney::of(2, 'EUR');

		$this->assertInstanceOf(ExtendedMoney::class, ExtendedMoney::of(0, 'EUR'));
		$this->assertInstanceOf(ExtendedMoney::class, ExtendedMoney::zero('EUR'));
		$this->assertInstanceOf(ExtendedMoney::class, ExtendedMoney::total($one, $two));
		$this->assertInstanceOf(ExtendedMoney::class, ExtendedMoney::create($one->getAmount(), $one->getCurrency(), new DefaultContext()));
		$this->assertInstanceOf(ExtendedMoney::class, ExtendedMoney::ofMinor(5, 'EUR'));
		$this->assertInstanceOf(ExtendedMoney::class, ExtendedMoney::max($one, $two));
		$this->assertInstanceOf(ExtendedMoney::class, ExtendedMoney::min($one, $two));
	}

	public function testMethods()
	{
		$one = ExtendedMoney::of(1, 'EUR');
		$two = ExtendedMoney::of(2, 'EUR');

		//Test returns of methods
		$this->assertInstanceOf(ExtendedMoney::class, $one->plus($two));
		$this->assertInstanceOf(ExtendedMoney::class, $one->minus($two));
		$this->assertInstanceOf(ExtendedMoney::class, $one->dividedBy(1));
		$this->assertInstanceOf(ExtendedMoney::class, $one->multipliedBy(1));
		$this->assertInstanceOf(ExtendedMoney::class, $one->to(new DefaultContext()));
		$this->assertInstanceOf(ExtendedMoney::class, $one->abs());
		$this->assertInstanceOf(ExtendedMoney::class, $one->negated());
		$this->assertInstanceOf(ExtendedMoney::class, $one->convertedTo('USD', '1.21'));
		$this->assertInstanceOf(ExtendedMoney::class, $one->quotient(2));

		//Test array returns of methods
		$this->assertContainsOnlyInstancesOf(ExtendedMoney::class, $one->allocate(1,2));
		$this->assertContainsOnlyInstancesOf(ExtendedMoney::class, $one->allocateWithRemainder(1,2));
		$this->assertContainsOnlyInstancesOf(ExtendedMoney::class, $one->quotientAndRemainder(2));
		$this->assertContainsOnlyInstancesOf(ExtendedMoney::class, $one->split(3));
		$this->assertContainsOnlyInstancesOf(ExtendedMoney::class, $one->splitWithRemainder(3));
	}
}
