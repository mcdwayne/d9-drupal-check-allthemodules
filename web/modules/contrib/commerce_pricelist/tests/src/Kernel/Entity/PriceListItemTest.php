<?php

namespace Drupal\Tests\commerce_pricelist\Kernel\Entity;

use Drupal\commerce_price\Price;
use Drupal\commerce_pricelist\Entity\PriceList;
use Drupal\commerce_pricelist\Entity\PriceListItem;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Tests\commerce_pricelist\Kernel\PriceListKernelTestBase;

/**
 * Tests the price list item entity.
 *
 * @coversDefaultClass \Drupal\commerce_pricelist\Entity\PriceListItem
 * @group commerce_pricelist
 */
class PriceListItemTest extends PriceListKernelTestBase {

  /**
   * A test price list.
   *
   * @var \Drupal\commerce_pricelist\Entity\PriceListInterface
   */
  protected $priceList;

  /**
   * A test variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $variation;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $price_list = PriceList::create([
      'type' => 'commerce_product_variation',
      'name' => 'B2B pricing',
      'stores' => [$this->store->id()],
    ]);
    $price_list->save();

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => 'TEST_' . strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 1,
      'price' => new Price('12.00', 'USD'),
    ]);
    $variation->save();

    $product = Product::create([
      'type' => 'default',
      'title' => 'Default testing product',
      'variations' => [$variation->id()],
    ]);
    $product->save();

    $this->priceList = $this->reloadEntity($price_list);
    $this->variation = $this->reloadEntity($variation);
  }

  /**
   * @covers ::getPriceList
   * @covers ::getPriceListId
   * @covers ::getPurchasableEntity
   * @covers ::setPurchasableEntity
   * @covers ::getPurchasableEntityId
   * @covers ::setPurchasableEntityId
   * @covers ::getQuantity
   * @covers ::setQuantity
   * @covers ::getListPrice
   * @covers ::setListPrice
   * @covers ::getPrice
   * @covers ::setPrice
   * @covers ::isEnabled
   * @covers ::setEnabled
   */
  public function testPriceListItem() {
    $price_list_item = PriceListItem::create([
      'type' => 'commerce_product_variation',
      'price_list_id' => $this->priceList->id(),
    ]);

    $this->assertEquals($this->priceList, $price_list_item->getPriceList());
    $this->assertEquals($this->priceList->id(), $price_list_item->getPriceListId());

    $price_list_item->setPurchasableEntity($this->variation);
    $this->assertEquals($this->variation, $price_list_item->getPurchasableEntity());
    $price_list_item->set('purchasable_entity', NULL);

    $price_list_item->setPurchasableEntityId($this->variation->id());
    $this->assertEquals($this->variation->id(), $price_list_item->getPurchasableEntityId());

    $price_list_item->setQuantity('10');
    $this->assertEquals('10', $price_list_item->getQuantity());

    $list_price = new Price('11', 'USD');
    $price_list_item->setListPrice($list_price);
    $this->assertEquals($list_price, $price_list_item->getListPrice());

    $price = new Price('9', 'USD');
    $price_list_item->setPrice($price);
    $this->assertEquals($price, $price_list_item->getPrice());

    $this->assertTrue($price_list_item->isEnabled());
    $price_list_item->setEnabled(FALSE);
    $this->assertFalse($price_list_item->isEnabled());
  }

  /**
   * Test that price list items are deleted after their variation is deleted.
   */
  public function testDeletion() {
    $price_list_item = PriceListItem::create([
      'type' => 'commerce_product_variation',
      'price_list_id' => $this->priceList->id(),
      'purchasable_entity' => $this->variation->id(),
      'quantity' => '10',
    ]);
    $price_list_item->save();

    $this->variation->delete();
    $this->assertNull($this->reloadEntity($price_list_item));
  }

}
