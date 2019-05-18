<?php

namespace Drupal\Tests\commerce_wishlist_api\Functional;

use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Core\Url;
use Drupal\Tests\rest\Functional\CookieResourceTestTrait;
use Drupal\Tests\rest\Functional\ResourceTestBase;

/**
 * Defines base class for commerce_wishlist_api test cases.
 */
abstract class WishlistResourceTestBase extends ResourceTestBase {

  use CookieResourceTestTrait;
  use StoreCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $auth = 'cookie';

  /**
   * The store entity.
   *
   * @var \Drupal\commerce_store\Entity\Store
   */
  protected $store;

  /**
   * The wishlist manager.
   *
   * @var \Drupal\commerce_wishlist\WishlistManagerInterface
   */
  protected $wishlistManager;

  /**
   * The wishlist provider.
   *
   * @var \Drupal\commerce_wishlist\WishlistProviderInterface
   */
  protected $wishlistProvider;

  /**
   * A variation to add to wishlists.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation;

  /**
   * A second variation to add to wishlists.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $variation2;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_wishlist_api',
    'commerce',
    'commerce_wishlist',
    'commerce_store',
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->store = $this->createStore();
    $this->wishlistManager = \Drupal::service('commerce_wishlist.wishlist_manager');
    $this->wishlistProvider = \Drupal::service('commerce_wishlist.wishlist_provider');

    // Create a product variation.
    $this->variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 1000,
        'currency_code' => 'USD',
      ],
    ]);

    // Create a second product variation.
    $this->variation2 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'number' => 500,
        'currency_code' => 'USD',
      ],
    ]);

    // We need a product too otherwise tests complain about the missing
    // backreference.
    $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$this->variation, $this->variation2],
    ]);

    $auth = isset(static::$auth) ? [static::$auth] : [];
    $this->provisionResource([static::$format], $auth);

    $this->initAuthentication();
  }

  /**
   * {@inheritdoc}
   */
  protected function assertNormalizationEdgeCases($method, Url $url, array $request_options) {}

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {}

  /**
   * {@inheritdoc}
   */
  protected function getExpectedBcUnauthorizedAccessMessage($method) {}

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {}

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['restful get ' . static::$resourceConfigId]);
        break;

      case 'POST':
        $this->grantPermissionsToTestedRole(['restful post ' . static::$resourceConfigId]);
        $this->initAuthentication();
        break;

      case 'PATCH':
        $this->grantPermissionsToTestedRole(['restful patch ' . static::$resourceConfigId]);
        $this->initAuthentication();
        break;

      case 'DELETE':
        $this->grantPermissionsToTestedRole(['restful delete ' . static::$resourceConfigId]);
        $this->initAuthentication();
        break;

      default:
        throw new \UnexpectedValueException();
    }
  }

  /**
   * Creates a new entity.
   *
   * @param string $entity_type
   *   The entity type to be created.
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity.
   */
  protected function createEntity($entity_type, array $values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
    $entity = $storage->create($values);
    $status = $entity->save();
    // The newly saved entity isn't identical to a loaded one, and would fail
    // comparisons.
    $entity = $storage->load($entity->id());

    return $entity;
  }

}
