<?php

namespace Drupal\Tests\bigcommerce\Functional;

use BigCommerce\Api\v3\Model\CatalogSummaryResponse;
use Drupal\bigcommerce\Exception\UnconfiguredException;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the BigCommerce ClientFactory.
 *
 * This is a BrowserTestBase so we can use BigCommerce API stubbing.
 *
 * @group bigcommerce
 */
class ClientFactoryTest extends BrowserTestBase {
  use StoreCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'bigcommerce',
    'bigcommerce_test',
  ];

  /**
   * Tests.
   */
  public function testClientFactory() {

    try {
      \Drupal::service('bigcommerce.catalog');
      $this->fail('Excepted runtime exception not thrown');
    }
    catch (UnconfiguredException $e) {
      $this->assertEquals('BigCommerce API is not configured', $e->getMessage());
    }

    // Configure BigCommerce to use the stub.
    $config = $this->config('bigcommerce.settings');
    $config->set('api', [
      'path' => Url::fromUri('base://bigcommerce_stub/connection')->setAbsolute()->toString(),
      'access_token' => 'an access token',
      'client_id' => 'a client ID',
      'client_secret' => 'a client secret',
      'timeout' => 15,
    ]);
    $config->save();

    try {
      \Drupal::service('bigcommerce.catalog');
      $this->fail('Excepted runtime exception not thrown');
    }
    catch (UnconfiguredException $e) {
      $this->assertEquals('BigCommerce requires a default commerce store', $e->getMessage());
    }

    // Create a store, it is the automatically the default store.
    $this->createStore();

    /** @var \BigCommerce\Api\v3\Api\CatalogApi $catalog_client */
    $catalog_client = \Drupal::service('bigcommerce.catalog');
    $this->assertInstanceOf(CatalogSummaryResponse::class, $catalog_client->catalogSummaryGet());
  }

}
