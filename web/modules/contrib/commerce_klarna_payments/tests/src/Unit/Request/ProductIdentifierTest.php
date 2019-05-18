<?php

namespace Drupal\Tests\commerce_klarna_payments\Unit\Request;

use Drupal\commerce_klarna_payments\Klarna\Request\ProductIdentifier;
use Drupal\Tests\UnitTestCase;

/**
 * Product identifier request unit tests.
 *
 * @group commerce_klarna_payments
 * @coversDefaultClass \Drupal\commerce_klarna_payments\Klarna\Request\ProductIdentifier
 */
class ProductIdentifierTest extends UnitTestCase {

  /**
   * Tests toArray() method.
   *
   * @covers \Drupal\commerce_klarna_payments\Klarna\Request\ProductIdentifier
   */
  public function testToArray() {
    $expected = [
      'category_path' => '1231',
      'global_trade_item_number' => '7980432',
      'manufacturer_part_number' => 'dsadsa',
      'brand' => 'Nokia',
    ];

    $product = new ProductIdentifier();
    $product->setCategoryPath($expected['category_path'])
      ->setGlobalTradeItemNumber($expected['global_trade_item_number'])
      ->setManufacturerPartNumber($expected['manufacturer_part_number'])
      ->setBrandName($expected['brand']);

    $this->assertEquals($expected, $product->toArray());
  }

}
