<?php

namespace Drupal\Tests\gopay\Unit\Item;

use Drupal\Tests\UnitTestCase;
use Drupal\gopay\Item\Item;
use GoPay\Definition\Payment\PaymentItemType;
use GoPay\Definition\Payment\VatRate;
use Drupal\gopay\Exception\GoPayInvalidSettingsException;

/**
 * @coversDefaultClass \Drupal\gopay\Item\Item
 * @group gopay
 */
class ItemTest extends UnitTestCase {

  /**
   * Test default values.
   */
  public function testDefaultValues() {
    $expected_config = [
      'type' => PaymentItemType::ITEM,
      'name' => 'name',
      'product_url' => NULL,
      'ean' => NULL,
      'amount' => 1,
      'count' => 1,
      'vat_rate' => NULL,
    ];

    $item = new Item();
    $item->setName('name');
    $item->setAmount(1);
    $item_config = $item->toArray();

    $this->assertArrayEquals($expected_config, $item_config);
  }

  /**
   * Test setting of all values. In cascade style.
   */
  public function testAllSetters() {
    $expected_config = [
      'type' => PaymentItemType::DELIVERY,
      'name' => 'name of item',
      'product_url' => 'http://foobar.baz',
      'ean' => 'EAN001',
      'amount' => 1000,
      'count' => 10,
      'vat_rate' => VatRate::RATE_4,
    ];

    $item_config = (new Item())
      ->setType(PaymentItemType::DELIVERY)
      ->setName('name of item')
      ->setProductUrl('http://foobar.baz')
      ->setEan('EAN001')
      ->setAmount(1000)
      ->setCount(10)
      ->setVatRate(VatRate::RATE_4)
      ->toArray();

    $this->assertArrayEquals($expected_config, $item_config);
  }

  /**
   * Test setting amount in units.
   */
  public function testAmountInUnits() {
    $item = new Item();
    $item->setName('name');
    $item->setAmount(10, FALSE);
    $item_config = $item->toArray();

    $this->assertEquals(1000, $item_config['amount']);
  }

  /**
   * Test missing name property.
   */
  public function testMissingName() {
    $this->setExpectedException(GoPayInvalidSettingsException::class);
    $item = new Item();
    $item->setAmount(1);
    $item->toArray();
  }

  /**
   * Test missing amount property.
   */
  public function testMissingAmount() {
    $this->setExpectedException(GoPayInvalidSettingsException::class);
    $item = new Item();
    $item->setName('name');
    $item->toArray();
  }

}
