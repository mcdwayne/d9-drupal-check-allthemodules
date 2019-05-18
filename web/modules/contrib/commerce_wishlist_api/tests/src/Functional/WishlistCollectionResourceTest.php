<?php

namespace Drupal\Tests\commerce_wishlist_api\Functional;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;

/**
 * Tests the wishlist collection resource.
 *
 * @group commerce_wishlist_api
 */
class WishlistCollectionResourceTest extends WishlistResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $resourceConfigId = 'commerce_wishlist_collection';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->setUpAuthorization('GET');
  }

  /**
   * Tests that wishlist that doesn't belong to account can't be retrieved.
   */
  public function testNoWishlistAvailable() {
    $url = Url::fromUri('base:wishlist');
    $url->setOption('query', ['_format' => static::$format]);
    $request_options = $this->getAuthenticationRequestOptions('GET');

    $wishlist = $this->wishlistProvider->createWishlist('default');
    $this->assertInstanceOf(WishlistInterface::class, $wishlist);

    $response = $this->request('GET', $url, $request_options);

    $this->assertResourceResponse(200, FALSE, $response, [
      'config:rest.resource.commerce_wishlist_collection',
      'config:rest.settings',
      'http_response',
    ], ['wishlist'], FALSE, 'MISS');

    $response_body = Json::decode((string) $response->getBody());
    $this->assertEmpty($response_body);
  }

  /**
   * Gets wishlists via the REST API.
   */
  public function testGetWishlists() {
    $url = Url::fromUri('base:wishlist');
    $url->setOption('query', ['_format' => static::$format]);
    $request_options = $this->getAuthenticationRequestOptions('GET');

    $wishlist = $this->wishlistProvider->createWishlist('default', $this->account);
    $this->assertInstanceOf(WishlistInterface::class, $wishlist);

    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response, [
      'commerce_wishlist:1',
      'config:rest.resource.commerce_wishlist_collection',
      'config:rest.settings',
      'http_response',
    ], ['wishlist'], FALSE, 'MISS');

    $response_body = Json::decode((string) $response->getBody());
    $this->assertEquals(count($response_body), 1);
    $response_body = $response_body[0];
    $this->assertEquals($response_body['wishlist_id'], $wishlist->id());
    $this->assertEmpty($response_body['wishlist_items']);
  }

}
