<?php

namespace Drupal\Tests\bigcommerce\Functional;

use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Core\Url;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests syncing product categories from BigCommerce.
 *
 * @group bigcommerce
 */
class ProductCategorySyncTest extends BrowserTestBase implements MigrateMessageInterface {
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
      ->loadByProperties(['vid' => 'bigcommerce_product_category']);
    $this->assertCount(0, $terms);
    $this->executeMigrations('bigcommerce_product_category');
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'bigcommerce_product_category']);
    $this->assertCount(6, $terms);

    // Test the bigcommerce_id field.
    $this->assertSame(20, Term::load(1)->bigcommerce_id->value);
    $this->assertSame(18, Term::load(2)->get('bigcommerce_id')->value);
    $this->assertSame(19, Term::load(3)->get('bigcommerce_id')->value);
    $this->assertSame(21, Term::load(4)->get('bigcommerce_id')->value);
    $this->assertSame(22, Term::load(5)->get('bigcommerce_id')->value);
    $this->assertSame(23, Term::load(6)->get('bigcommerce_id')->value);

    // Test terms not created via syncing are not broken.
    $term = Term::create([
      'vid' => 'bigcommerce_product_category',
      'name' => 'a fake test',
    ]);
    $this->assertNull($term->get('bigcommerce_id')->value);
    $term->save();
    $this->assertNull(Term::load(7)->get('bigcommerce_id')->value);
  }

}
