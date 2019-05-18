<?php

namespace Drupal\Tests\commerce_wishlist_api\Functional;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\Core\Url;
use GuzzleHttp\RequestOptions;

/**
 * Tests wishlist api access check.
 *
 * @group commerce_wishlist_api
 */
class WishlistAccessApiResourceTest extends WishlistResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $resourceConfigId = 'commerce_wishlist_canonical';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Parent will provision resource for canonical; need others here.
    $auth = isset(static::$auth) ? [static::$auth] : [];

    self::$resourceConfigId = 'commerce_wishlist_collection';
    $this->provisionResource([static::$format], $auth);
    self::$resourceConfigId = 'commerce_wishlist_update_item';
    $this->provisionResource([static::$format], $auth);

    $this->initAuthentication();
    $this->setUpAuthorization('GET');
    $this->setUpAuthorization('PATCH');
  }

  /**
   * Check access for route with no parameters (wishlist collection).
   */
  public function testNoParameters() {
    $request_options = $this->getAuthenticationRequestOptions('GET');

    $url = Url::fromUri('base:wishlist');
    $url->setOption('query', ['_format' => static::$format]);

    $wishlist = $this->wishlistProvider->createWishlist('default', $this->account);
    $this->assertInstanceOf(WishlistInterface::class, $wishlist);

    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response, [
      'commerce_wishlist:1',
      'config:rest.resource.commerce_wishlist_collection',
      'config:rest.settings', 'http_response',
    ], ['wishlist'], FALSE, 'MISS');
  }

  /**
   * Check no access for missing wishlist (wishlist canonical).
   */
  public function testNoWishlist() {
    $request_options = $this->getAuthenticationRequestOptions('GET');

    // Request for wishlist that does not exist.
    $url = Url::fromUri('base:wishlist/99');
    $url->setOption('query', ['_format' => static::$format]);

    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceErrorResponse(404, 'The "commerce_wishlist" parameter was not converted for the path "/wishlist/{commerce_wishlist}" (route name: "rest.commerce_wishlist_canonical.GET")', $response);
  }

  /**
   * Check no access for wishlist not belonging to user (wishlist canonical).
   */
  public function testNotUsersWishlist() {
    $request_options = $this->getAuthenticationRequestOptions('GET');

    $wishlist = $this->wishlistProvider->createWishlist('default');
    $this->wishlistManager->addEntity($wishlist, $this->variation, 2);

    $url = Url::fromUri('base:wishlist/' . $wishlist->id());
    $url->setOption('query', ['_format' => static::$format]);

    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceErrorResponse(403, '', $response);
  }

  /**
   * Check no access for wishlist item not in wishlist (wishlist update item).
   */
  public function testInvalidWishlistItemWishlist() {
    $request_options = $this->getAuthenticationRequestOptions('PATCH');
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    // Create a wishlist with an wishlist item.
    $wishlist = $this->wishlistProvider->createWishlist('default', $this->account);
    $this->wishlistManager->addEntity($wishlist, $this->variation, 2);

    $url = Url::fromUri('base:wishlist/' . $wishlist->id() . '/items/2');
    $url->setOption('query', ['_format' => static::$format]);
    $request_options[RequestOptions::BODY] = '{"quantity":"1"}';

    // Create wishlist item in another wishlist.
    $another_wishlist = $this->wishlistProvider->createWishlist('default');
    $this->wishlistManager->addEntity($another_wishlist, $this->variation, 2);

    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(403, '', $response);
  }

}
