<?php

namespace Drupal\Tests\commerce_pricelist\Kernel;

use Drupal\commerce_pricelist\Entity\PriceListItem;

/**
 * Tests the bundles for commerce_pricelist and commerce_pricelist_item.
 *
 * @group commerce_pricelist
 */
class BundleTest extends PriceListKernelTestBase {

  /**
   * Tests available bundles.
   */
  public function testAvailableBundles() {
    $bundle_info = $this->container->get('entity_type.bundle.info')->getAllBundleInfo();
    $price_list_bundles = $bundle_info['commerce_pricelist'];
    $price_list_item_bundles = $bundle_info['commerce_pricelist_item'];

    $this->assertCount(1, $price_list_bundles);
    $this->assertTrue(isset($price_list_bundles['commerce_product_variation']));
    $this->assertCount(1, $price_list_item_bundles);
    $this->assertTrue(isset($price_list_item_bundles['commerce_product_variation']));

    // The test module provides its own purchasable entity type.
    $this->installModule('commerce_pricelist_test');
    $this->container->get('entity_type.manager')->clearCachedDefinitions();
    $this->container->get('entity_type.bundle.info')->clearCachedBundles();

    $bundle_info = $this->container->get('entity_type.bundle.info')->getAllBundleInfo();
    $price_list_bundles = $bundle_info['commerce_pricelist'];
    $price_list_item_bundles = $bundle_info['commerce_pricelist_item'];

    $this->assertCount(2, $price_list_bundles);
    $this->assertTrue(isset($price_list_bundles['commerce_pricelist_widget']));
    $this->assertCount(2, $price_list_item_bundles);
    $this->assertTrue(isset($price_list_item_bundles['commerce_pricelist_widget']));
  }

  /**
   * Tests the 'purchasable_entity' field definition.
   */
  public function testPurchasableEntityFieldDefinition() {
    /** @var \Drupal\commerce_pricelist\Entity\PriceListItem $price_list_item */
    $price_list_item = PriceListItem::create([
      'type' => 'commerce_product_variation',
      'price_list_id' => 1,
    ]);
    $price_list_item->save();

    $entity_type_id = $price_list_item->get('purchasable_entity')->getFieldDefinition()->getSetting('target_type');
    $this->assertEquals('commerce_product_variation', $entity_type_id);
  }

}
