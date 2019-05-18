<?php

namespace Drupal\Tests\commerce_claim_gift_aid\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Test whether the default gift aid config text has been imported.
 *
 * @group commerce_claim_gift_aid
 */
class CheckGiftAidConfigTest extends CommerceBrowserTestBase {

  /**
   * Module to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_claim_gift_aid',
  ];

  /**
   * Check whether the default config has imported properly.
   */
  public function testDoesDefaultGiftAidConfigExist() {
    $this->drupalGet('admin/config/commerce-claim-gift-aid');
    $this->assertSession()->pageTextContains(t('I am a UK taxpayer and would like INSERT COMPANY NAME HERE to treat any donation I make today, in the future or have made in the past 4 years as Gift Aid donations, until I notify you otherwise. I understand that if I pay less Income Tax and/or Capital Gains Tax than the amount of Gift Aid claimed on all my donations in that tax year it is my responsibility to pay the difference.'));
  }

}
