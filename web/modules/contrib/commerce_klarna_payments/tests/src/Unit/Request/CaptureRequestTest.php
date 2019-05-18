<?php

namespace Drupal\Tests\commerce_klarna_payments\Unit\Request;

use Drupal\commerce_klarna_payments\Klarna\Data\OrderItemInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\ShippingInformationInterface;
use Drupal\commerce_klarna_payments\Klarna\Request\Order\CaptureRequest;
use Drupal\Tests\UnitTestCase;

/**
 * Capture request unit tests.
 *
 * @group commerce_klarna_payments
 * @coversDefaultClass \Drupal\commerce_klarna_payments\Klarna\Request\Order\CaptureRequest
 */
class CaptureRequestTest extends UnitTestCase {

  /**
   * Tests toArray() method.
   *
   * @covers ::toArray
   * @covers ::setCapturedAmount
   * @covers ::setDescription
   * @covers ::setShippingDelay
   */
  public function testToArray() {
    $expected = [
      'captured_amount' => 2500,
      'description' => 'Description test',
      'shipping_delay' => 5,
    ];
    $request = new CaptureRequest();
    $request->setCapturedAmount($expected['captured_amount'])
      ->setDescription($expected['description'])
      ->setShippingDelay($expected['shipping_delay']);

    $this->assertEquals($expected, $request->toArray());
  }

  /**
   * @covers ::setShippingInformation
   */
  public function testSetShippingInformation() {
    $info = $this->getMockBuilder(ShippingInformationInterface::class)
      ->getMock();
    $info->expects($this->any())
      ->method('toArray')
      ->willReturn([
        'shipping_company' => 'Itella',
      ]);

    $request = (new CaptureRequest())->setShippingInformation($info);

    $this->assertEquals([
      'shipping_info' => [
        'shipping_company' => 'Itella',
      ],
    ], $request->toArray());
  }

  /**
   * @covers ::setOrderItems
   * @covers ::addOrderItem
   */
  public function testSetOrderItem() {
    $orderItem = $this->getMockBuilder(OrderItemInterface::class)
      ->getMock();
    $orderItem->expects($this->any())
      ->method('toArray')
      ->willReturn([
        'name' => '123',
      ]);

    $request = new CaptureRequest();
    $request->setOrderItems([$orderItem]);

    $orderLine = ['name' => '123'];

    // Test with one order lines.
    $this->assertEquals(['order_lines' => [$orderLine]], $request->toArray());

    $request->addOrderItem($orderItem);
    // Test with multiple order lines.
    $this->assertEquals(['order_lines' => [$orderLine, $orderLine]], $request->toArray());
  }

}
