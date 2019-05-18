<?php

namespace Drupal\Tests\commerce_claim_gift_aid\Functional;

use Drupal\Tests\commerce_order\Functional\OrderBrowserTestBase;

/**
 * Test whether an order item can be set to be eligible to claim for gift aid.
 *
 * @group commerce_claim_gift_aid
 */
class CheckGiftAidOrderItemTest extends OrderBrowserTestBase {

  /**
   * Module to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_claim_gift_aid',
  ];

  /**
   * Verify that the order item edit form has the eligible for gift aid field.
   */
  public function testIsBooleanFieldOnOrderItemEditForm() {
    $this->drupalGet('admin/commerce/config/order-item-types/default/edit');
    $this->assertSession()->pageTextContains(t('Order item is eligible for gift aid?'));
  }

  /**
   * Check whether an order item can store whether its eligible for gift aid.
   */
  public function testOrderItemIsEligibleForGiftAid() {
    $this->drupalGet('admin/commerce/config/order-item-types/default/edit');

    $edit = [
      'gift_aid' => 1,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains(t('Saved the default order item type.'));

  }

}
