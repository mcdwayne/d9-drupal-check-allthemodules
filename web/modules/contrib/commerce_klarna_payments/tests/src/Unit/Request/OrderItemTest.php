<?php

namespace Drupal\Tests\commerce_klarna_payments\Unit\Request;

use Drupal\commerce_klarna_payments\Klarna\Request\OrderItem;
use Drupal\Tests\UnitTestCase;

/**
 * Order item request unit tests.
 *
 * @group commerce_klarna_payments
 * @coversDefaultClass \Drupal\commerce_klarna_payments\Klarna\Request\OrderItem
 */
class OrderItemTest extends UnitTestCase {

  /**
   * @covers ::setType
   * @expectedException \InvalidArgumentException
   */
  public function testTypeException() {
    (new OrderItem())->setType('invalid');
  }

  /**
   * Tests toArray() method.
   *
   * @covers \Drupal\commerce_klarna_payments\Klarna\Request\OrderItem
   */
  public function testToArray() {
    $expected = [
      'type' => 'physical',
      'name' => 'Product 1',
      'product_url' => 'http://localhost',
      'image_url' => 'http://localhost/image.jpg',
      'quantity' => 5,
      'quantity_unit' => 'kg',
      'unit_price' => 1000,
      'tax_rate' => 25,
      'total_tax_amount' => 255,
      'total_amount' => 5000,
      'reference' => '12345',
    ];
    $item = new OrderItem();
    $item->setType($expected['type'])
      ->setName($expected['name'])
      ->setProductUrl($expected['product_url'])
      ->setImageUrl($expected['image_url'])
      ->setUnitPrice($expected['unit_price'])
      ->setQuantity($expected['quantity'])
      ->setQuantityUnit($expected['quantity_unit'])
      ->setTaxRate($expected['tax_rate'])
      ->setTotalTaxAmount($expected['total_tax_amount'])
      ->setTotalAmount($expected['total_amount'])
      ->setReference($expected['reference']);

    $this->assertEquals($expected, $item->toArray());
  }

}
