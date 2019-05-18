<?php

namespace Drupal\Tests\commerce_addon\Kernel;

use Drupal\commerce_addon\Entity\Addon;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests add-ons and order / order item integration
 *
 * @group commerce_addon
 */
class OrderIntegrationTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_promotion',
    'commerce_addon',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_addon');
    $this->installConfig([
      'commerce_order',
      'commerce_addon',
    ]);
  }

  /**
   * Tests that an order item is created.
   */
  public function testOrderItemCreation() {
    $addon = Addon::create([
      'title' => 'Enable super powers',
      'description' => 'Adding this will give your product super powers',
      'price' => new Price('25.00', 'USD'),
      'type' => 'default',
    ]);
    $addon->save();

    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->container->get('entity_type.manager')->getStorage('commerce_order_item');

    $order_item = $order_item_storage->createFromPurchasableEntity($addon);
    $this->assertEquals($order_item->bundle(), 'addon');
    $this->assertEquals($order_item->label(), $addon->label());
    $this->assertEquals($addon->getPrice(), $order_item->getUnitPrice());
  }

}
