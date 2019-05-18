<?php

namespace Drupal\Tests\commerce_wishlist\Kernel;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\commerce_wishlist\Exception\DuplicateWishlistException;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the wishlist provider.
 *
 * @coversDefaultClass \Drupal\commerce_wishlist\WishlistProvider
 * @group commerce_wishlist
 */
class WishlistProviderTest extends EntityKernelTestBase {

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The wishlist provider.
   *
   * @var \Drupal\commerce_wishlist\WishlistProviderInterface
   */
  protected $wishlistProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'router');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_wishlist');
    $this->installEntitySchema('commerce_wishlist_item');
    $this->installConfig(['commerce_wishlist']);

    $this->anonymousUser = $this->createUser([
      'uid' => 0,
      'name' => '',
      'status' => 0,
    ]);
    $this->authenticatedUser = $this->createUser();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->wishlistProvider = $this->container->get('commerce_wishlist.wishlist_provider');
  }

  /**
   * Tests wishlist creation for an anonymous user.
   *
   * @covers ::createWishlist
   */
  public function testCreateAnonymousWishlist() {
    $wishlist_type = 'default';
    $wishlist = $this->wishlistProvider->createWishlist($wishlist_type, $this->anonymousUser);
    $this->assertInstanceOf(WishlistInterface::class, $wishlist);

    // Trying to recreate the same wishlist should throw an exception.
    $this->setExpectedException(DuplicateWishlistException::class);
    $this->wishlistProvider->createWishlist($wishlist_type, $this->anonymousUser);
  }

  /**
   * Tests getting an anonymous user's wishlist.
   *
   * @covers ::getWishlist
   * @covers ::getWishlistId
   * @covers ::getWishlists
   * @covers ::getWishlistIds
   */
  public function testGetAnonymousWishlist() {
    $this->wishlistProvider->createWishlist('default', $this->anonymousUser);
    $wishlist = $this->wishlistProvider->getWishlist('default', $this->anonymousUser);
    $this->assertInstanceOf(WishlistInterface::class, $wishlist);

    $wishlist_id = $this->wishlistProvider->getWishlistId('default', $this->anonymousUser);
    $this->assertEquals(1, $wishlist_id);

    $wishlists = $this->wishlistProvider->getWishlists($this->anonymousUser);
    $this->assertContainsOnlyInstancesOf(WishlistInterface::class, $wishlists);

    $wishlist_ids = $this->wishlistProvider->getWishlistIds($this->anonymousUser);
    $this->assertContains(1, $wishlist_ids);
  }

  /**
   * Tests creating a wishlist for an authenticated user.
   *
   * @covers ::createWishlist
   */
  public function testCreateAuthenticatedWishlist() {
    $wishlist = $this->wishlistProvider->createWishlist('default', $this->authenticatedUser);
    $this->assertInstanceOf(WishlistInterface::class, $wishlist);

    // Trying to recreate the same wishlist should throw an exception.
    $this->setExpectedException(DuplicateWishlistException::class);
    $this->wishlistProvider->createWishlist('default', $this->authenticatedUser);
  }

  /**
   * Tests getting an authenticated user's wishlist.
   *
   * @covers ::getWishlist
   * @covers ::getWishlistId
   * @covers ::getWishlists
   * @covers ::getWishlistIds
   */
  public function testGetAuthenticatedWishlist() {
    $this->wishlistProvider->createWishlist('default', $this->authenticatedUser);

    $wishlist = $this->wishlistProvider->getWishlist('default', $this->authenticatedUser);
    $this->assertInstanceOf(WishlistInterface::class, $wishlist);

    $wishlist_id = $this->wishlistProvider->getWishlistId('default', $this->authenticatedUser);
    $this->assertEquals(1, $wishlist_id);

    $wishlists = $this->wishlistProvider->getWishlists($this->authenticatedUser);
    $this->assertContainsOnlyInstancesOf(WishlistInterface::class, $wishlists);

    $wishlist_ids = $this->wishlistProvider->getWishlistIds($this->authenticatedUser);
    $this->assertContains(1, $wishlist_ids);
  }

}
