<?php

namespace Tests\AppBundle\Utils;

use AppBundle\Sylius\Order\OrderInterface;
use AppBundle\Entity\Restaurant;
use AppBundle\Utils\PreparationTimeCalculator;
use AppBundle\Utils\ShippingTimeCalculator;
use AppBundle\Utils\ShippingDateFilter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class ShippingDateFilterTest extends TestCase
{
    private $restaurant;
    private $preparationTimeCalculator;
    private $shippingTimeCalculator;

    private $shippingDateFilter;

    public function setUp()
    {
        $this->restaurant = $this->prophesize(Restaurant::class);
        $this->preparationTimeCalculator = $this->prophesize(PreparationTimeCalculator::class);
        $this->shippingTimeCalculator = $this->prophesize(ShippingTimeCalculator::class);

        $this->shippingDateFilter = new ShippingDateFilter(
            $this->preparationTimeCalculator->reveal(),
            $this->shippingTimeCalculator->reveal()
        );
    }

    public function testDateInThePast()
    {
        $order = $this->prophesize(OrderInterface::class);

        $shippingDate = new \DateTime('2018-10-12 19:30:00');
        $now = new \DateTime('2018-10-12 20:00:00');

        $this->assertFalse($this->shippingDateFilter->accept($order->reveal(), $shippingDate, $now));
    }

    public function acceptProvider()
    {
        return [
            [
                new \DateTime('2018-10-12 19:15:00'),
                '15 minutes',
                '10 minutes',
                new \DateTime('2018-10-12 19:40:00'),
                false,
            ],
            [
                new \DateTime('2018-10-12 19:25:00'),
                '10 minutes',
                '10 minutes',
                new \DateTime('2018-10-12 19:40:00'),
                false,
            ],
            [
                new \DateTime('2018-10-12 19:25:00'),
                '15 minutes',
                '10 minutes',
                new \DateTime('2018-10-12 19:55:00'),
                true,
            ],
        ];
    }

    /**
     * @dataProvider acceptProvider
     */
    public function testAccept(\DateTime $now, $preparationTime, $shippingTime, \DateTime $shippingDate, $expected)
    {
        $order = $this->prophesize(OrderInterface::class);

        $this->preparationTimeCalculator
            ->calculate(Argument::type(OrderInterface::class))
            ->willReturn($preparationTime);

        $this->shippingTimeCalculator
            ->calculate(Argument::type(OrderInterface::class))
            ->willReturn($shippingTime);

        $this->assertEquals($expected, $this->shippingDateFilter->accept($order->reveal(), $shippingDate, $now));
    }
}