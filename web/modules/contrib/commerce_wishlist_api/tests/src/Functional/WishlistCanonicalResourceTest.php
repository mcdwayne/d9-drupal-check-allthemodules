<?php

namespace Drupal\Tests\commerce_wishlist_api\Functional;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;

/**
 * Tests the wishlist canonical resource.
 *
 * @group commerce_wishlist_api
 */
class WishlistCanonicalResourceTest extends WishlistResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $resourceConfigId = 'commerce_wishlist_canonical';

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
    $request_options = $this->getAuthenticationRequestOptions('GET');

    /** @var \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist */
    $wishlist = \Drupal::service('commerce_wishlist.wishlist_provider')->createWishlist('default');
    $this->assertInstanceOf(WishlistInterface::class, $wishlist);

    $url = Url::fromUri('base:wishlist/' . $wishlist->id());
    $url->setOption('query', ['_format' => static::$format]);

    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceErrorResponse(403, '', $response);
  }

  /**
   * Creates a wishlist and retrieves it via the REST API.
   */
  public function testGetWishlist() {
    $request_options = $this->getAuthenticationRequestOptions('GET');

    // Add a wishlist that does belong to the account.
    /** @var \Drupal\commerce_wishlist\Entity\WishlistInterface $wishlist */
    $wishlist = \Drupal::service('commerce_wishlist.wishlist_provider')->createWishlist('default', $this->account);
    $this->assertInstanceOf(WishlistInterface::class, $wishlist);

    $url = Url::fromUri('base:wishlist/' . $wishlist->id());
    $url->setOption('query', ['_format' => static::$format]);

    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response, [
      'commerce_wishlist:1',
      'config:rest.resource.commerce_wishlist_canonical',
      'config:rest.settings',
      'http_response',
    ], [''], FALSE, 'MISS');

    $response_body = Json::decode((string) $response->getBody());
    $this->assertEquals($response_body['wishlist_id'], $wishlist->id());
    $this->assertEmpty($response_body['wishlist_items']);

    // Add wishlist item to the wishlist.
    $this->wishlistManager->addEntity($wishlist, $this->variation, 2);
    $this->assertEquals(count($wishlist->getItems()), 1);
    $items = $wishlist->getItems();
    $wishlist_item = $items[0];

    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response, [
      'commerce_wishlist:1',
      'config:rest.resource.commerce_wishlist_canonical',
      'config:rest.settings',
      'http_response',
    ], [''], FALSE, 'MISS');

    $response_body = Json::decode((string) $response->getBody());
    $this->assertEquals(count($response_body['wishlist_items']), 1);
    $this->assertEquals($response_body['wishlist_items'][0]['wishlist_item_id'], $wishlist_item->id());
    $this->assertEquals($response_body['wishlist_items'][0]['purchasable_entity']['variation_id'], $wishlist_item->getPurchasableEntityId());
    $this->assertEquals($response_body['wishlist_items'][0]['title'], $wishlist_item->getTitle());
    $this->assertEquals($response_body['wishlist_items'][0]['quantity'], $wishlist_item->getQuantity());
  }

}
