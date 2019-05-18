<?php

namespace Drupal\Tests\commerce_wishlist\Kernel\QueueWorker;

use Drupal\commerce_wishlist\Entity\Wishlist;
use Drupal\commerce_wishlist\Entity\WishlistItem;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\Tests\commerce_cart\Traits\CartManagerTestTrait;

/**
 * Tests deleting wishlist items via cron.
 *
 * @coversDefaultClass \Drupal\commerce_wishlist\Plugin\QueueWorker\WishlistItemDelete
 * @group commerce_wishlist
 */
class WishlistItemDeleteTest extends CommerceKernelTestBase {

  use CartManagerTestTrait;

  /**
   * A sample user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_wishlist',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_wishlist');
    $this->installEntitySchema('commerce_wishlist_item');
    $this->installConfig(['commerce_order']);
    $this->installConfig(['commerce_product']);
    $this->installCommerceCart();
    $this->installConfig(['commerce_wishlist']);

    $user = $this->createUser();
    $this->user = $this->reloadEntity($user);
  }

  /**
   * Tests deleting wishlist items.
   *
   * @covers ::processItem
   */
  public function testDelete() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $first_variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $first_variation->save();

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $second_variation = ProductVariation::create([
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $second_variation->save();

    /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item */
    $first_wishlist_item = WishlistItem::create([
      'type' => 'commerce_product_variation',
      'purchasable_entity' => $first_variation,
    ]);
    $first_wishlist_item->save();

    /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item */
    $second_wishlist_item = WishlistItem::create([
      'type' => 'commerce_product_variation',
      'purchasable_entity' => $second_variation,
    ]);
    $second_wishlist_item->save();

    $wishlist = Wishlist::create([
      'type' => 'default',
      'name' => 'My wishlist',
      'wishlist_items' => [$first_wishlist_item, $second_wishlist_item],
    ]);
    $wishlist->save();

    $first_variation->delete();
    $this->container->get('cron')->run();

    // Confirm that the first wishlist item has been deleted.
    $first_wishlist_item = $this->reloadEntity($first_wishlist_item);
    $second_wishlist_item = $this->reloadEntity($second_wishlist_item);
    $this->assertEmpty($first_wishlist_item);
    $this->assertNotEmpty($second_wishlist_item);

    /** @var \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist */
    $wishlist = $this->reloadEntity($wishlist);
    $wishlist_items = $wishlist->getItems();
    $wishlist_item = reset($wishlist_items);
    $this->assertCount(1, $wishlist_items);
    $this->assertEquals($second_wishlist_item->id(), $wishlist_item->id());
  }

}
