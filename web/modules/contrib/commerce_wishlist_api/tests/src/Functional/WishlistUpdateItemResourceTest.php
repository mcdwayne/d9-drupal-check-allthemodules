<?php

namespace Drupal\Tests\commerce_wishlist_api\Functional;

use Drupal\commerce_wishlist\Entity\Wishlist;
use Drupal\commerce_wishlist\Entity\WishlistItem;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use GuzzleHttp\RequestOptions;

/**
 * Tests the wishlist update items resource.
 *
 * @group commerce_wishlist_api
 */
class WishlistUpdateItemResourceTest extends WishlistResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $resourceConfigId = 'commerce_wishlist_update_item';

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

    $url = Url::fromUri('base:wishlist/1/items/1');
    $url->setOption('query', ['_format' => static::$format]);
    $request_options[RequestOptions::BODY] = '{"quantity":"1"}';

    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(404, FALSE, $response);
  }

  /**
   * Tests patch when wishlist item does not exist in wishlist.
   */
  public function testMissingWishlistItem() {
    $request_options = $this->getAuthenticationRequestOptions('PATCH');
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    $wishlist = $this->wishlistProvider->createWishlist('default', $this->account);
    $this->wishlistManager->addEntity($wishlist, $this->variation, 2);

    $url = Url::fromUri('base:wishlist/' . $wishlist->id() . '/items/2');
    $url->setOption('query', ['_format' => static::$format]);
    $request_options[RequestOptions::BODY] = '{"quantity":"1"}';

    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(404, FALSE, $response);
  }

  /**
   * Tests wishlist that does not belong to the account.
   */
  public function testWishlistNoAccess() {
    $request_options = $this->getAuthenticationRequestOptions('PATCH');
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    // Wishlist that does not belong to the account.
    $wishlist = $this->wishlistProvider->createWishlist('default');
    $this->wishlistManager->addEntity($wishlist, $this->variation, 2);
    $this->assertEquals(count($wishlist->getItems()), 1);
    $items = $wishlist->getItems();
    $wishlist_item = $items[0];

    $url = Url::fromUri('base:wishlist/' . $wishlist->id() . '/items/' . $wishlist_item->id());
    $url->setOption('query', ['_format' => static::$format]);

    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(403, FALSE, $response);
  }

  /**
   * Tests malformed payloads.
   */
  public function testInvalidPayload() {
    $request_options = $this->getAuthenticationRequestOptions('PATCH');
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    $wishlist = $this->wishlistProvider->createWishlist('default', $this->account);
    $this->wishlistManager->addEntity($wishlist, $this->variation, 2);
    $this->assertEquals(count($wishlist->getItems()), 1);
    $items = $wishlist->getItems();
    $wishlist_item = $items[0];

    $url = Url::fromUri('base:wishlist/' . $wishlist->id() . '/items/' . $wishlist_item->id());
    $url->setOption('query', ['_format' => static::$format]);

    $request_options[RequestOptions::BODY] = '{"quantity":"1", "another_field":"1"}';
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'You only have access to update the quantity', $response);

    $request_options[RequestOptions::BODY] = '{"not_quantity":"1"}';
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'You only have access to update the quantity', $response);

    $request_options[RequestOptions::BODY] = '{"quantity":"-1"}';
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'Quantity must be positive value', $response);
  }

  /**
   * Patch an wishlist item for a session's wishlist via the REST API.
   */
  public function testPatchWishlistItem() {
    $request_options = $this->getAuthenticationRequestOptions('PATCH');
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    /** @var \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist */
    $wishlist = $this->wishlistProvider->createWishlist('default', $this->account);
    $this->wishlistManager->addEntity($wishlist, $this->variation, 2);
    $this->wishlistManager->addEntity($wishlist, $this->variation2, 5);
    $this->assertEquals(count($wishlist->getItems()), 2);
    $items = $wishlist->getItems();
    $wishlist_item = $items[0];
    $wishlist_item_2 = $items[1];
    $this->assertEquals($wishlist_item->getQuantity(), 2);
    $this->assertEquals($wishlist_item_2->getQuantity(), 5);

    // Patch quantity for second wishlist item.
    $url = Url::fromUri('base:wishlist/' . $wishlist->id() . '/items/' . $wishlist_item_2->id());
    $url->setOption('query', ['_format' => static::$format]);
    $request_options[RequestOptions::BODY] = '{"quantity":"1"}';
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);

    // Verify wishlist item updated.
    $this->container->get('entity_type.manager')->getStorage('commerce_wishlist_item')->resetCache([
      $wishlist_item->id(),
      $wishlist_item_2->id(),
    ]);
    $wishlist_item = WishlistItem::load($wishlist_item->id());
    $wishlist_item_2 = WishlistItem::load($wishlist_item_2->id());
    $this->assertEquals($wishlist_item->getQuantity(), 2);
    $this->assertEquals($wishlist_item_2->getQuantity(), 1);

    $this->container->get('entity_type.manager')->getStorage('commerce_wishlist')->resetCache([$wishlist->id()]);
    $wishlist = Wishlist::load($wishlist->id());

    // Verify json response.
    $response_body = Json::decode((string) $response->getBody());
    $this->assertEquals($response_body['wishlist_id'], $wishlist->id());
    $this->assertEquals($response_body['name'], $wishlist->getName());
    $this->assertEquals(count($response_body['wishlist_items']), 2);

    // First wishlist item.
    $item_delta = ($response_body['wishlist_items'][0]['wishlist_item_id'] == $wishlist_item->id()) ? 0 : 1;
    $this->assertEquals($response_body['wishlist_items'][$item_delta]['wishlist_item_id'], $wishlist_item->id());
    $this->assertEquals($response_body['wishlist_items'][$item_delta]['purchasable_entity']['variation_id'], $wishlist_item->getPurchasableEntityId());
    $this->assertEquals($response_body['wishlist_items'][$item_delta]['quantity'], $wishlist_item->getQuantity());

    // Second wishlist item.
    $item_delta = ($response_body['wishlist_items'][0]['wishlist_item_id'] == $wishlist_item_2->id()) ? 0 : 1;
    $this->assertEquals($response_body['wishlist_items'][$item_delta]['wishlist_item_id'], $wishlist_item_2->id());
    $this->assertEquals($response_body['wishlist_items'][$item_delta]['purchasable_entity']['variation_id'], $wishlist_item_2->getPurchasableEntityId());
    $this->assertEquals($response_body['wishlist_items'][$item_delta]['quantity'], $wishlist_item_2->getQuantity());
  }

}
