<?php

namespace Drupal\Tests\uc_fulfillment\Functional;

use Drupal\Tests\uc_fulfillment\Traits\FulfillmentTestTrait;
use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Base class for fulfillment backend functionality tests.
 */
abstract class FulfillmentTestBase extends UbercartBrowserTestBase {
  use FulfillmentTestTrait;

  public static $modules = [
    'uc_payment',
    'uc_payment_pack',
    'uc_fulfillment',
  ];
  public static $adminPermissions = ['fulfill orders'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Ensure test mails are logged.
    \Drupal::configFactory()->getEditable('system.mail')
      ->set('interface.uc_order', 'test_mail_collector')
      ->save();

    // Set the default ship-from country to be the same as the store country
    // that was determined in parent::setUp().
    $store_country = \Drupal::configFactory()
      ->get('uc_store.settings')
      ->get('address.country');
    \Drupal::configFactory()->getEditable('uc_quote.settings')
      ->set('ship_from_address.country', $store_country)
      ->save();
  }

}
