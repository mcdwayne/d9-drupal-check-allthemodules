<?php

namespace Drupal\Tests\commerce_wishlist_api\Functional;

use Drupal\commerce_wishlist\Entity\Wishlist;
use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\Core\Url;

/**
 * Tests the wishlist clear resource.
 *
 * @group commerce_wishlist_api
 */
class WishlistClearResourceTest extends WishlistResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $resourceConfigId = 'commerce_wishlist_clear';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->setUpAuthorization('DELETE');
  }

  /**
   * Removes all items from a wishlist.
   */
  public function testClearWishlist() {
    $request_options = $this->getAuthenticationRequestOptions('DELETE');

    // Failed request to clear wishlist that doesn't belong to the account.
    $not_my_wishlist = $this->wishlistProvider->createWishlist('default');
    $this->assertInstanceOf(WishlistInterface::class, $not_my_wishlist);
    $this->wishlistManager->addEntity($not_my_wishlist, $this->variation, 2);
    $this->assertEquals(count($not_my_wishlist->getItems()), 1);

    $url = Url::fromUri('base:wishlist/' . $not_my_wishlist->id() . '/items');
    $url->setOption('query', ['_format' => static::$format]);

    $response = $this->request('DELETE', $url, $request_options);
    $this->assertResourceErrorResponse(403, '', $response);

    // Add a wishlist that does belong to the account.
    $wishlist = $this->wishlistProvider->createWishlist('default', $this->account);
    $this->assertInstanceOf(WishlistInterface::class, $wishlist);
    $this->wishlistManager->addEntity($wishlist, $this->variation, 2);
    $this->wishlistManager->addEntity($wishlist, $this->variation2, 5);
    $this->assertEquals(count($wishlist->getItems()), 2);

    $url = Url::fromUri('base:wishlist/' . $wishlist->id() . '/items');
    $url->setOption('query', ['_format' => static::$format]);

    $response = $this->request('DELETE', $url, $request_options);
    $this->assertResourceErrorResponse(204, '', $response);

    $this->container->get('entity_type.manager')->getStorage('commerce_wishlist')->resetCache([$wishlist->id()]);
    $wishlist = Wishlist::load($wishlist->id());

    $this->assertEquals(count($wishlist->getItems()), 0);
  }

}
