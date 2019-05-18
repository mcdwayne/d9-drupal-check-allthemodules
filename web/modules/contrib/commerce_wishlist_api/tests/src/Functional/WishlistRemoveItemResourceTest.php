<?php

namespace Drupal\Tests\commerce_wishlist_api\Functional;

use Drupal\commerce_wishlist\Entity\Wishlist;
use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\Core\Url;

/**
 * Tests the wishlist remove item resource.
 *
 * @group commerce_wishlist_api
 */
class WishlistRemoveItemResourceTest extends WishlistResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $resourceConfigId = 'commerce_wishlist_remove_item';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->setUpAuthorization('DELETE');
  }

  /**
   * Test request to delete item from non-existent wishlist.
   */
  public function testNoWishlistRemoveItem() {
    $request_options = $this->getAuthenticationRequestOptions('DELETE');
    $url = Url::fromUri('base:wishlist/1/items/1');
    $url->setOption('query', ['_format' => static::$format]);

    $response = $this->request('DELETE', $url, $request_options);
    $this->assertResourceErrorResponse(404, 'The "commerce_wishlist" parameter was not converted for the path "/wishlist/{commerce_wishlist}/items/{commerce_wishlist_item}" (route name: "rest.commerce_wishlist_remove_item.DELETE")', $response);
  }

  /**
   * Removes wishlist items via the REST API.
   */
  public function testRemoveItem() {
    $request_options = $this->getAuthenticationRequestOptions('DELETE');

    // Failed request to delete item from another user's wishlist.
    $not_my_wishlist = $this->wishlistProvider->createWishlist('default');
    $this->assertInstanceOf(WishlistInterface::class, $not_my_wishlist);
    $this->wishlistManager->addEntity($not_my_wishlist, $this->variation, 2);
    $this->assertEquals(count($not_my_wishlist->getItems()), 1);
    $items = $not_my_wishlist->getItems();
    $not_my_wishlist_item = $items[0];

    $url = Url::fromUri('base:wishlist/' . $not_my_wishlist->id() . '/items/' . $not_my_wishlist_item->id());
    $url->setOption('query', ['_format' => static::$format]);

    $response = $this->request('DELETE', $url, $request_options);
    $this->assertResourceErrorResponse(403, '', $response);

    // Add a wishlist that does belong to the account.
    $wishlist = $this->wishlistProvider->createWishlist('default', $this->account);
    $this->assertInstanceOf(WishlistInterface::class, $wishlist);
    $this->wishlistManager->addEntity($wishlist, $this->variation, 2);
    $this->wishlistManager->addEntity($wishlist, $this->variation2, 5);
    $this->assertEquals(count($wishlist->getItems()), 2);
    $items = $wishlist->getItems();
    $wishlist_item = $items[0];
    $wishlist_item2 = $items[1];

    // Request for wishlist item that does not exist in wishlist should fail.
    $url = Url::fromUri('base:wishlist/' . $wishlist->id() . '/items/' . $not_my_wishlist_item->id());
    $url->setOption('query', ['_format' => static::$format]);

    $response = $this->request('DELETE', $url, $request_options);
    $this->assertResourceErrorResponse(403, '', $response);
    $this->container->get('entity_type.manager')->getStorage('commerce_wishlist')->resetCache([$not_my_wishlist->id(), $wishlist->id()]);
    $not_my_wishlist = Wishlist::load($not_my_wishlist->id());
    $wishlist = Wishlist::load($wishlist->id());

    $this->assertEquals(count($not_my_wishlist->getItems()), 1);
    $this->assertEquals(count($wishlist->getItems()), 2);

    // Delete second wishlist item from the wishlist.
    $url = Url::fromUri('base:wishlist/' . $wishlist->id() . '/items/' . $wishlist_item2->id());
    $url->setOption('query', ['_format' => static::$format]);

    $response = $this->request('DELETE', $url, $request_options);
    $this->assertResourceResponse(204, '', $response);
    $this->container->get('entity_type.manager')->getStorage('commerce_wishlist')->resetCache([$wishlist->id()]);
    $wishlist = Wishlist::load($wishlist->id());

    $this->assertEquals(count($wishlist->getItems()), 1);
    $items = $wishlist->getItems();
    /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $remaining_wishlist_item */
    $remaining_wishlist_item = $items[0];
    $this->assertEquals($wishlist_item->id(), $remaining_wishlist_item->id());

    // Delete remaining wishlist item from the wishlist.
    $url = Url::fromUri('base:wishlist/' . $wishlist->id() . '/items/' . $remaining_wishlist_item->id());
    $url->setOption('query', ['_format' => static::$format]);

    $response = $this->request('DELETE', $url, $request_options);
    $this->assertResourceResponse(204, '', $response);
    $this->container->get('entity_type.manager')->getStorage('commerce_wishlist')->resetCache([$wishlist->id()]);
    $wishlist = Wishlist::load($wishlist->id());

    $this->assertEquals(count($wishlist->getItems()), 0);
  }

}
