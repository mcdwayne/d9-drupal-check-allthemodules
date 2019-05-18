<?php

namespace Drupal\Tests\commerce_wishlist\Kernel;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_wishlist\Entity\Wishlist;
use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Drupal\commerce_wishlist\Entity\WishlistType;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the wishlist manager.
 *
 * @coversDefaultClass \Drupal\commerce_wishlist\WishlistManager
 * @group commerce_wishlist
 */
class WishlistManagerTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'options',
    'entity',
    'entity_reference_revisions',
    'views',
    'address',
    'profile',
    'inline_entity_form',
    'state_machine',
    'commerce',
    'commerce_cart',
    'commerce_order',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'commerce_wishlist',
  ];

  /**
   * Anonymous user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $anonymousUser;

  /**
   * Registered user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authenticatedUser;

  /**
   * The purchasable entity.
   *
   * @var \Drupal\commerce\PurchasableEntityInterface
   */
  protected $purchasableEntity;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The wishlist manager.
   *
   * @var \Drupal\commerce_wishlist\WishlistManagerInterface
   */
  protected $wishlistManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'router');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_wishlist_item');
    $this->installEntitySchema('commerce_wishlist');
    $this->installConfig(['commerce_wishlist']);

    $wishlist_type = WishlistType::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $wishlist_type->save();
    commerce_wishlist_add_wishlist_items_field($wishlist_type);

    ProductVariationType::create([
      'id' => 'test',
      'label' => 'Test',
      'generateTitle' => FALSE,
    ])->save();

    $this->anonymousUser = $this->createUser([
      'uid' => 0,
      'name' => '',
      'status' => 0,
    ]);
    $this->authenticatedUser = $this->createUser();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->wishlistManager = $this->container->get('commerce_wishlist.wishlist_manager');
  }

  /**
   * Tests wishlist item creation.
   *
   * @covers ::createWishlistItem
   */
  public function testCreateWishlistItem() {
    $variation = ProductVariation::create([
      'type' => 'test',
      'sku' => strtolower($this->randomMachineName()),
      'title' => 'My product',
      'status' => 1,
    ]);
    $variation->save();

    $wishlist_item = $this->wishlistManager->createWishlistItem($variation, 2);
    $this->assertInstanceOf(WishlistItemInterface::class, $wishlist_item);
    $this->assertEquals(2, $wishlist_item->getQuantity());
    $this->assertEquals('My product', $wishlist_item->getTitle());
  }

  /**
   * Tests adding wishlist item to a wishlist.
   *
   * @covers ::addWishlistItem
   */
  public function testAddWishlistItem() {
    $variation = ProductVariation::create([
      'type' => 'test',
      'sku' => strtolower($this->randomMachineName()),
      'title' => 'My product',
      'status' => 1,
    ]);
    $variation->save();

    $wishlist = Wishlist::create([
      'type' => 'test',
      'name' => 'My wishlist',
    ]);
    $wishlist->save();

    $wishlist_item = $this->wishlistManager->createWishlistItem($variation, 2);

    $wishlist_item = $this->wishlistManager->addWishlistItem($wishlist, $wishlist_item);
    $this->assertInstanceOf(WishlistItemInterface::class, $wishlist_item);
    $this->assertEquals(2, $wishlist_item->getQuantity());
    $this->assertEquals('My product', $wishlist_item->getTitle());
    $this->assertTrue($wishlist->hasItem($wishlist_item));
  }

  /**
   * Tests adding a purchasable entity to a wishlist.
   *
   * @covers ::addEntity
   */
  public function testAddEntity() {
    $variation = ProductVariation::create([
      'type' => 'test',
      'sku' => strtolower($this->randomMachineName()),
      'title' => 'My product',
      'status' => 1,
    ]);
    $variation->save();

    $wishlist = Wishlist::create([
      'type' => 'test',
      'name' => 'My wishlist',
    ]);
    $wishlist->save();

    $wishlist_item = $this->wishlistManager->addEntity($wishlist, $variation, 3);
    $this->assertInstanceOf(WishlistItemInterface::class, $wishlist_item);
    $this->assertEquals(3, $wishlist_item->getQuantity());
    $this->assertEquals('My product', $wishlist_item->getTitle());
    $this->assertTrue($wishlist->hasItem($wishlist_item));
  }

  /**
   * Tests emptying a wishlist.
   *
   * @covers ::emptyWishlist
   */
  public function testEmptyWishlist() {
    $variation = ProductVariation::create([
      'type' => 'test',
      'sku' => strtolower($this->randomMachineName()),
      'title' => 'My product',
      'status' => 1,
    ]);
    $variation->save();

    $wishlist = Wishlist::create([
      'type' => 'test',
      'name' => 'My wishlist',
    ]);
    $wishlist->save();
    $this->wishlistManager->addEntity($wishlist, $variation);

    $this->assertTrue($wishlist->hasItems());
    $this->wishlistManager->emptyWishlist($wishlist);
    $this->assertFalse($wishlist->hasItems());
  }

  /**
   * Tests updating a wishlist item of a wishlist.
   *
   * @covers ::updateWishlistItem
   */
  public function testUpdateWishlistItem() {
    $variation = ProductVariation::create([
      'type' => 'test',
      'sku' => strtolower($this->randomMachineName()),
      'title' => 'My product',
      'status' => 1,
    ]);
    $variation->save();

    $wishlist = Wishlist::create([
      'type' => 'test',
      'name' => 'My wishlist',
    ]);
    $wishlist->save();

    $wishlist_item = $this->wishlistManager->createWishlistItem($variation, 2);

    $wishlist_item = $this->wishlistManager->addWishlistItem($wishlist, $wishlist_item);
    $wishlist_item->setQuantity(5);
    $this->wishlistManager->updateWishlistItem($wishlist, $wishlist_item);
    /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item */
    $wishlist_item = $this->reloadEntity($wishlist_item);
    $this->assertEquals(5, $wishlist_item->getQuantity());
  }

  /**
   * Tests removing a wishlist item from a wishlist.
   *
   * @covers ::removeWishlistItem
   */
  public function testRemoveWishlistItem() {
    $variation = ProductVariation::create([
      'type' => 'test',
      'sku' => strtolower($this->randomMachineName()),
      'title' => 'My product',
      'status' => 1,
    ]);
    $variation->save();

    $wishlist = Wishlist::create([
      'type' => 'test',
      'name' => 'My wishlist',
    ]);
    $wishlist->save();

    $wishlist_item = $this->wishlistManager->addEntity($wishlist, $variation);
    $this->assertTrue($wishlist->hasItems());

    $this->wishlistManager->removeWishlistItem($wishlist, $wishlist_item);
    $this->assertFalse($wishlist->hasItems());
  }

}
