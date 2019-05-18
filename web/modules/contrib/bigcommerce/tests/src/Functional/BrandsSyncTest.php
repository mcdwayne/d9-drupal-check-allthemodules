<?php

namespace Drupal\Tests\bigcommerce\Functional;

use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Core\Url;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests syncing brands from BigCommerce.
 *
 * @group bigcommerce
 */
class BrandsSyncTest extends BrowserTestBase implements MigrateMessageInterface {
  use BigCommerceTestTrait;
  use StoreCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'bigcommerce',
    'bigcommerce_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Configure API to use the stub.
    $this->config('bigcommerce.settings')
      ->set('api.path', Url::fromUri('base://bigcommerce_stub/connection')->setAbsolute()->toString())
      ->set('api.access_token', 'an access token')
      ->set('api.client_id', 'a client ID')
      ->set('api.client_secret', 'a client secret')
      ->save();
    $this->createStore();
  }

  public function testSync() {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'bigcommerce_product_brand']);
    $this->assertCount(0, $terms);
    $this->executeMigrations('bigcommerce_product_brand');
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'bigcommerce_product_brand']);
    $this->assertCount(5, $terms);

    // Test the bigcommerce_id field.
    $apple_term = Term::load(5);
    $this->assertSame(39, $apple_term->bigcommerce_id->value);
    $this->assertSame('Apple', $apple_term->label());
    $this->assertSame('Apple', $apple_term->field_product_brand_image->alt);
    $this->assertSame('public://bigcommerce/product-brand/apple.jpg', $apple_term->field_product_brand_image->entity->getFileUri());
    $this->assertFileExists($apple_term->field_product_brand_image->entity->getFileUri());
  }

}
