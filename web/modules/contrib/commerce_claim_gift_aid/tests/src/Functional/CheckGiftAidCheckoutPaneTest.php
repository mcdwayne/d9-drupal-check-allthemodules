<?php

namespace Drupal\Tests\commerce_claim_gift_aid\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Test whether we have the gift aid pane in the default checkout flow.
 *
 * @group commerce_claim_gift_aid
 */
class CheckGiftAidCheckoutPaneTest extends CommerceBrowserTestBase {

  /**
   * Module to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_claim_gift_aid',
    'commerce_checkout',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_checkout_flow',
    ], parent::getAdministratorPermissions());
  }

  /**
   * Verify that the gift aid setting is in a checkout flow pane.
   */
  public function testDoesGiftAidSettingsPaneExist() {
    $this->drupalGet('admin/commerce/config/checkout-flows/manage/default');
    $this->assertSession()->pageTextContains(t('Gift Aid Declaration'));
  }

}
