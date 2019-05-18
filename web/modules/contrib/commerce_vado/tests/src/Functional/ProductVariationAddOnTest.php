<?php

namespace Drupal\Tests\commerce_vado\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Functional test of the formatter and form.
 *
 * @group commerce_vado
 */
class ProductVariationAddOnTest extends CommerceBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
    'commerce_order',
    'commerce_vado',
    'entity_reference_revisions',
    'profile',
    'system',
  ];

  protected $store;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $neededFields = [
      'field_variation_addon' => t('Product Variation Add On'),
      'field_variation_addon_sync' => t('Add On Sync Quantity'),
    ];
    foreach ($neededFields as $neededField => $label) {
      FieldStorageConfig::create([
        'entity_type' => 'commerce_product_variation',
        'field_name' => $neededField,
        'type' => $neededField === 'field_variation_addon' ? 'entity_reference' : 'boolean',
        'settings' => [
          'target_type' => 'commerce_product_variation',
        ],
        'cardinality' => 1,
      ])->save();

      FieldConfig::create([
        'entity_type' => 'commerce_product_variation',
        'field_name' => $neededField,
        'bundle' => 'default',
        'label' => $label,
      ])->save();
    }
  }

  /**
   * Tests VADO without quantity sync.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testVariationAddonNoSync() {
    /** @var \Drupal\commerce_product\Entity\Product $parent_product */
    $parent_product = $this->createProduct();
    /** @var \Drupal\commerce_product\Entity\ProductVariation $parent_product_variation */
    $parent_product_variation = $parent_product->getDefaultVariation();
    /** @var \Drupal\commerce_product\Entity\Product $child_product */
    $child_product = $this->createProduct();
    /** @var \Drupal\commerce_product\Entity\ProductVariation $chid_product_variation */
    $child_product_variation = $child_product->getDefaultVariation();

    $parent_product_variation->set('field_variation_addon', $child_product_variation->id());
    $parent_product_variation->save();

    $order_item = OrderItem::create([
      'type' => 'default',
      'unit_price' => $parent_product_variation->getPrice(),
      'purchased_entity' => $parent_product_variation,
      'quantity' => 1,
    ]);
    $order_item->save();
    $order_item = OrderItem::load($order_item->id());

    Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
    ])->addItem($order_item)
      ->save();
    $order = Order::load(1);

    $this->assertCount(2, $order->getItems());
  }

  /**
   * Tests VADO with quantity sync.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testVariationAddonSync() {
    /** @var \Drupal\commerce_product\Entity\Product $parent_product */
    $parent_product = $this->createProduct();
    /** @var \Drupal\commerce_product\Entity\ProductVariation $parent_product_variation */
    $parent_product_variation = $parent_product->getDefaultVariation();
    /** @var \Drupal\commerce_product\Entity\Product $child_product */
    $child_product = $this->createProduct();
    /** @var \Drupal\commerce_product\Entity\ProductVariation $chid_product_variation */
    $chid_product_variation = $child_product->getDefaultVariation();

    $parent_product_variation->set('field_variation_addon', $chid_product_variation->id());
    $parent_product_variation->set('field_variation_addon_sync', TRUE);
    $parent_product_variation->save();

    $order_item = OrderItem::create([
      'type' => 'default',
      'unit_price' => $parent_product_variation->getPrice(),
      'purchased_entity' => $parent_product_variation->id(),
      'quantity' => 32,
    ]);

    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store,
      'uid' => $this->createUser()->id(),
      'order_items' => [$order_item],
    ]);
    $order->save();
    $order = Order::load($order->id());

    $this->assertCount(2, $order->getItems());

    $parent_qty = $order->getItems()[0]->getQuantity();
    $child_qty = $order->getItems()[1]->getQuantity();
    $this->assertEquals($parent_qty, $child_qty);
  }

  /**
   * Creates random product w/ variation.
   *
   * @return mixed
   *   Product entity.
   */
  private function createProduct() {
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'body' => ['value' => $this->randomString()],
      'variations' => [
        $this->createEntity('commerce_product_variation', [
          'type' => 'default',
          'sku' => $this->randomMachineName(),
          'price' => [
            'number' => '10.99',
            'currency_code' => 'USD',
          ],
        ]),
      ],
    ]);
    return $product;
  }

}
