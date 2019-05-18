<?php

namespace Drupal\Tests\commerce_wishlist_api\Functional;

use Drupal\commerce_wishlist\Entity\Wishlist;
use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\commerce_wishlist\Entity\WishlistItem;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use GuzzleHttp\RequestOptions;

/**
 * Tests the wishlist update items resource.
 *
 * @group commerce_wishlist_api
 */
class WishlistUpdateItemsResourceTest extends WishlistResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $resourceConfigId = 'commerce_wishlist_update_items';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->setUpAuthorization('PATCH');
  }

  /**
   * Tests patch when wishlist does not exist.
   */
  public function testMissingWishlist() {
    $request_options = $this->getAuthenticationRequestOptions('PATCH');
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    // Attempt to patch items when no wishlist exists.
    $url = Url::fromUri('base:wishlist/1/items');
    $url->setOption('query', ['_format' => static::$format]);
    $request_options[RequestOptions::BODY] = '{"1":{"quantity":"1"}}';

    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(404, FALSE, $response);
  }

  /**
   * Tests malformed payloads.
   */
  public function testInvalidPayload() {
    $request_options = $this->getAuthenticationRequestOptions('PATCH');
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    $wishlist = $this->wishlistProvider->createWishlist('default', $this->account);
    $url = Url::fromUri('base:wishlist/' . $wishlist->id() . '/items');
    $url->setOption('query', ['_format' => static::$format]);

    $request_options[RequestOptions::BODY] = '{"1":{"quantity":"1"}}';
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'Unable to find wishlist item 1', $response);

    // Create an item in another wishlist.
    $another_wishlist = $this->wishlistProvider->createWishlist('default');
    $this->wishlistManager->addEntity($another_wishlist, $this->variation, 2);

    $request_options[RequestOptions::BODY] = '{"1":{"quantity":"1"}}';
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'Invalid wishlist item', $response);

    // Give the original wishlist a valid wishlist item.
    $this->wishlistManager->addEntity($wishlist, $this->variation, 2);

    $request_options[RequestOptions::BODY] = '{"2":{"quantity":"1", "another_field":"1"}}';
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'You only have access to update the quantity', $response);

    $request_options[RequestOptions::BODY] = '{"2":{"not_quantity":"1"}}';
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'You only have access to update the quantity', $response);

    $request_options[RequestOptions::BODY] = '{"2":{"quantity":"-1"}}';
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'Quantity must be positive value', $response);
  }

  /**
   * Patch wishlist items for a session's wishlist via the REST API.
   */
  public function testPatchWishlistItems() {
    $request_options = $this->getAuthenticationRequestOptions('PATCH');
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    // Wishlist that does not belong to the account.
    $not_my_wishlist = $this->wishlistProvider->createWishlist('default');
    $this->assertInstanceOf(WishlistInterface::class, $not_my_wishlist);
    $this->wishlistManager->addEntity($not_my_wishlist, $this->variation, 2);
    $this->assertEquals(count($not_my_wishlist->getItems()), 1);

    $url = Url::fromUri('base:wishlist/' . $not_my_wishlist->id() . '/items');
    $url->setOption('query', ['_format' => static::$format]);
    $request_options[RequestOptions::BODY] = '{"1":{"quantity":"1"},"2":{"quantity":"1.00"}}';

    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(403, FALSE, $response);

    // Add a wishlist that does belong to the account.
    $wishlist = $this->wishlistProvider->createWishlist('default', $this->account);
    $this->assertInstanceOf(WishlistInterface::class, $wishlist);
    $this->wishlistManager->addEntity($wishlist, $this->variation, 2);
    $this->wishlistManager->addEntity($wishlist, $this->variation2, 5);
    $this->assertEquals(count($wishlist->getItems()), 2);
    $items = $wishlist->getItems();
    $wishlist_item = $items[0];
    $wishlist_item2 = $items[1];

    // Attempt to update items in two different wishlists.
    $url = Url::fromUri('base:wishlist/' . $wishlist->id() . '/items');
    $url->setOption('query', ['_format' => static::$format]);
    $request_options[RequestOptions::BODY] = '{"1":{"quantity":"1"},"2":{"quantity":"1.00"}}';
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, FALSE, $response);

    // Update items in wishlist belonging to account.
    $url = Url::fromUri('base:wishlist/' . $wishlist->id() . '/items');
    $url->setOption('query', ['_format' => static::$format]);
    $request_options[RequestOptions::BODY] = '{"2":{"quantity":"1"},"3":{"quantity":"1.00"}}';
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);

    // Verify wishlist items properly updated.
    $this->container->get('entity_type.manager')->getStorage('commerce_wishlist_item')->resetCache([
      $wishlist_item->id(),
      $wishlist_item2->id(),
    ]);
    $wishlist_item = WishlistItem::load($wishlist_item->id());
    $wishlist_item2 = WishlistItem::load($wishlist_item2->id());
    $this->assertEquals($wishlist_item->getQuantity(), 1);
    $this->assertEquals($wishlist_item2->getQuantity(), 1);

    // Verify wishlist total properly updated.
    $this->container->get('entity_type.manager')->getStorage('commerce_wishlist')->resetCache([$wishlist->id()]);
    $wishlist = Wishlist::load($wishlist->id());

    // Verify json response.
    $response_body = Json::decode((string) $response->getBody());
    $this->assertEquals($response_body['wishlist_id'], $wishlist->id());
    $this->assertEquals($response_body['name'], $wishlist->getName());
    $this->assertEquals(count($response_body['wishlist_items']), 2);

    // First wishlist item.
    $item_delta = ($response_body['wishlist_items'][0]['wishlist_item_id'] == 2) ? 0 : 1;
    $this->assertEquals($response_body['wishlist_items'][$item_delta]['wishlist_item_id'], $wishlist_item->id());
    $this->assertEquals($response_body['wishlist_items'][$item_delta]['purchasable_entity']['variation_id'], $wishlist_item->getPurchasableEntityId());
    $this->assertEquals($response_body['wishlist_items'][$item_delta]['quantity'], $wishlist_item->getQuantity());

    // Second wishlist item.
    $item_delta = ($response_body['wishlist_items'][0]['wishlist_item_id'] == 3) ? 0 : 1;
    $this->assertEquals($response_body['wishlist_items'][$item_delta]['wishlist_item_id'], $wishlist_item2->id());
    $this->assertEquals($response_body['wishlist_items'][$item_delta]['purchasable_entity']['variation_id'], $wishlist_item2->getPurchasableEntityId());
    $this->assertEquals($response_body['wishlist_items'][$item_delta]['quantity'], $wishlist_item2->getQuantity());
  }

}
