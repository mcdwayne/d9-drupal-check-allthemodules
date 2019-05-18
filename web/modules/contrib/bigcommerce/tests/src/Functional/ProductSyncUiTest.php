<?php

namespace Drupal\Tests\bigcommerce\Functional;

use Drupal\Core\Url;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the cart functionality of the BigCommerce module.
 *
 * @group bigcommerce
 */
class ProductSyncUiTest extends CommerceBrowserTestBase {
  use BigCommerceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'bigcommerce',
    'bigcommerce_test',
  ];

  /**
   * Test cart.
   */
  public function testProductSyncUi() {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('admin/commerce/config/bigcommerce/product_sync');
    $assert->pageTextContains('Access denied');

    $this->drupalLogin($this->drupalCreateUser(['access bigcommerce administration pages']));
    $this->drupalGet('admin/commerce/config/bigcommerce/product_sync');
    // Ensure the user is redirected if BigCommerce needs to be configured.
    $assert->pageTextContains('BigCommerce must be configured before synchronising products.');
    $assert->addressEquals('admin/commerce/config/bigcommerce/settings');

    $config = $this->config('bigcommerce.settings');
    $config->set('api', [
      'path' => Url::fromUri('base://bigcommerce_stub/cart')->setAbsolute()->toString(),
      'access_token' => 'an access token',
      'client_id' => 'a client ID',
      'client_secret' => 'a client secret',
      'timeout' => 15,
    ]);
    $config->save();

    $this->drupalGet('admin/commerce/config/bigcommerce/product_sync');
    $assert->pageTextContains('BigCommerce product updates available.');
    $assert->addressEquals('admin/commerce/config/bigcommerce/product_sync');
    $assert->pageTextContains('bigcommerce_product_variation Idle 70 0 70 0');
    // Refresh till the batch is complete.
    $this->maximumMetaRefreshCount = NULL;
    $this->submitForm([], 'Sync products from BigCommerce');
    $assert->pageTextContains('BigCommerce synchronization successful');
    $assert->pageTextContains('bigcommerce_product_variation Idle 70 70 0 0');
    $this->assertMigrations();

    // There are new fields so we need to clear caches.
    $this->resetAll();

    // Test status message.
    $this->drupalGet('admin/commerce/config/bigcommerce/product_sync');
    $assert->pageTextContains('BigCommerce products up-to-date.');

    // Test updating a product title.
    $products = \Drupal::entityTypeManager()
      ->getStorage('commerce_product')
      ->loadByProperties(['title' => '[Sample] Smith Journal 13']);
    $product = reset($products);
    $this->assertEquals(111, $product->bigcommerce_id->value);

    \Drupal::state()->set('ProductSyncUiTest.name', 'A new product title');

    // Re-run the sync now the product title has been changed.
    $this->drupalGet('admin/commerce/config/bigcommerce/product_sync');
    $assert->pageTextContains('BigCommerce product updates available.');
    $assert->pageTextContains('bigcommerce_product Idle 15 15 0 1');
    $assert->pageTextContains('bigcommerce_product_variation Idle 70 70 0 1');

    $this->submitForm([], 'Sync products from BigCommerce');
    $assert->pageTextContains('BigCommerce synchronization successful');
    $assert->pageTextContains('BigCommerce products up-to-date.');
    $assert->pageTextContains('bigcommerce_product Idle 15 15 0 0');
    $assert->pageTextContains('bigcommerce_product_variation Idle 70 70 0 0');

    $this->assertMigrations();
    $products = \Drupal::entityTypeManager()
      ->getStorage('commerce_product')
      ->loadByProperties(['title' => '[Sample] Smith Journal 13']);
    $this->assertEmpty($products);
    $products = \Drupal::entityTypeManager()
      ->getStorage('commerce_product')
      ->loadByProperties(['title' => 'A new product title']);
    $product = reset($products);
    $this->assertEquals(111, $product->bigcommerce_id->value);
  }

}
