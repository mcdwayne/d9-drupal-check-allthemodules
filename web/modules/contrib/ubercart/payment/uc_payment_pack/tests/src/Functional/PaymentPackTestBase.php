<?php

namespace Drupal\Tests\uc_payment_pack\Functional;

use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Base class for payment method pack tests.
 */
abstract class PaymentPackTestBase extends UbercartBrowserTestBase {

  public static $modules = ['uc_payment', 'uc_payment_pack'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Log in and add a product to the cart for testing.
    $this->drupalLogin($this->adminUser);
    $this->addToCart($this->product);

    // Disable address panes during checkout.
    $edit = [
      'panes[delivery][status]' => FALSE,
      'panes[billing][status]' => FALSE,
    ];
    $this->drupalPostForm('admin/store/config/checkout', $edit, 'Save configuration');
  }

}
