<?php

namespace Drupal\Tests\commerce_wishlist_api\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use GuzzleHttp\RequestOptions;

/**
 * Tests the wishlist add resource.
 *
 * @group commerce_wishlist_api
 */
class WishlistAddResourceTest extends WishlistResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $resourceConfigId = 'commerce_wishlist_add';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->setUpAuthorization('POST');
  }

  /**
   * Tests malformed payloads.
   */
  public function testMalformedPayload() {
    $url = Url::fromUri('base:wishlist/add');
    $url->setOption('query', ['_format' => static::$format]);

    $request_options = $this->getAuthenticationRequestOptions('POST');
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    // Missing purchasable entity type.
    $request_options[RequestOptions::BODY] = '[{ "purchasable_entity_id": "1", "quantity": "1"}]';
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'You must specify a purchasable entity type for row: 0', $response);

    // Missing purchasable entity ID.
    $request_options[RequestOptions::BODY] = '[{ "purchasable_entity_type": "commerce_product_variation", "quantity": "1"}]';
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'You must specify a purchasable entity ID for row: 0', $response);

    // Invalid purchasable entity type.
    $request_options[RequestOptions::BODY] = '[{ "purchasable_entity_type": "invalid_type", "purchasable_entity_id": "1", "quantity": "1"}]';
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(422, 'You must specify a valid purchasable entity type for row: 0', $response);
  }

  /**
   * Tests invalid purchasable entity.
   */
  public function testInvalidPurchasableEntity() {
    $url = Url::fromUri('base:wishlist/add');
    $url->setOption('query', ['_format' => static::$format]);

    $request_options = $this->getAuthenticationRequestOptions('POST');
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    // Add item when no wishlist exists.
    $request_options[RequestOptions::BODY] = '[{ "purchasable_entity_type": "commerce_product_variation", "purchasable_entity_id": "99", "quantity": "1"}]';

    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $response_body = Json::decode((string) $response->getBody());
    $this->assertEmpty($response_body);
  }

  /**
   * Creates wishlist items for a session's wishlist via the REST API.
   */
  public function testPostWishlistItems() {
    $url = Url::fromUri('base:wishlist/add');
    $url->setOption('query', ['_format' => static::$format]);

    $request_options = $this->getAuthenticationRequestOptions('POST');
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    // Add item when no wishlist exists.
    $request_options[RequestOptions::BODY] = '[{ "purchasable_entity_type": "commerce_product_variation", "purchasable_entity_id": "1", "quantity": "1"}]';

    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $response_body = Json::decode((string) $response->getBody());
    $this->assertEquals(count($response_body), 1);
    $this->assertEquals(count($response_body), 1);
    $this->assertEquals($response_body[0]['wishlist_item_id'], 1);
    $this->assertEquals($response_body[0]['purchasable_entity']['variation_id'], 1);
    $this->assertEquals($response_body[0]['quantity'], 1);

    // Add two more of the same item.
    $request_options[RequestOptions::BODY] = '[{ "purchasable_entity_type": "commerce_product_variation", "purchasable_entity_id": "1", "quantity": "2"}]';

    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $response_body = Json::decode((string) $response->getBody());
    $this->assertEquals(count($response_body), 1);
    $this->assertEquals($response_body[0]['wishlist_item_id'], 1);
    $this->assertEquals($response_body[0]['quantity'], 3);

    // Add another item.
    $request_options[RequestOptions::BODY] = '[{ "purchasable_entity_type": "commerce_product_variation", "purchasable_entity_id": "2", "quantity": "5"}]';

    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $response_body = Json::decode((string) $response->getBody());
    $this->assertEquals(count($response_body), 1);
    $item_delta = ($response_body[0]['wishlist_item_id'] == 2) ? 0 : 1;
    $this->assertEquals($response_body[$item_delta]['quantity'], 5);
  }

}
