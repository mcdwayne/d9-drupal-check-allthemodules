<?php

namespace Drupal\Tests\commerce_amazon_lpa\Kernel;

use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests CurrentMerchantAccount.
 *
 * @group commerce_amazon_lpa
 */
class CurrentMerchantAccountTest extends CommerceKernelTestBase {

  public static $modules = [
    'commerce_amazon_lpa',
  ];

  /**
   * Tests that the default store's config is resolved.
   */
  public function testResolve() {
    /** @var \Drupal\commerce_store\StoreStorage $store_storage */
    $store_storage = $this->container->get('entity_type.manager')->getStorage('commerce_store');
    $store2 = $this->createStore(NULL, NULL, 'online', FALSE);
    $store3 = $this->createStore(NULL, NULL, 'online', FALSE);

    $this->config('commerce_amazon_lpa.settings')
      ->set('mode', 'test')
      ->set('merchant_information', [
        $this->store->uuid() => [
          'merchant_id' => 'AAA',
          'mws_access_key' => '',
          'mws_secret_key' => '',
          'lwa_client_id' => '',
          'region' => 'US',
          'langcode' => 'en-US',
        ],
        // Store 2 is not configured for Amazon Pay.
        $store3->uuid() => [
          'merchant_id' => 'CCC',
          'mws_access_key' => '',
          'mws_secret_key' => '',
          'lwa_client_id' => '',
          'region' => 'DE',
          'langcode' => 'de-DE',
        ],
      ])
      ->save();

    $current_merchant_account = $this->container->get('commerce_amazon_lpa.current_merchant_account');
    $current = $current_merchant_account->resolve();
    $this->assertNotEmpty($current);
    $this->assertEquals('AAA', $current['merchant_id']);

    $store_storage->markAsDefault($store3);

    // Push a new request to bust the current store service cache.
    $this->container->get('request_stack')->push(Request::createFromGlobals());
    $current_merchant_account = $this->container->get('commerce_amazon_lpa.current_merchant_account');
    $current = $current_merchant_account->resolve();
    $this->assertNotEmpty($current);
    $this->assertEquals('CCC', $current['merchant_id']);

    $store_storage->markAsDefault($store2);

    // Push a new request to bust the current store service cache.
    $this->container->get('request_stack')->push(Request::createFromGlobals());
    $current_merchant_account = $this->container->get('commerce_amazon_lpa.current_merchant_account');
    $current = $current_merchant_account->resolve();
    $this->assertEmpty($current);
  }

}
