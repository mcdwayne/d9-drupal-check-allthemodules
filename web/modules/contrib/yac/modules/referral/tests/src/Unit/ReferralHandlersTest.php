<?php

namespace Drupal\Tests\yac_referral\Unit;

use Drupal\yac_referral\ReferralHandlers;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the ReferralHandlers class methods
 *
 * @group yac_referral
 */
class ReferralHandlersTest extends UnitTestCase {

  /**
   * Tests the ReferralHandlers::validCode() method.
   */
  public function testValidCode() {
    $referral_handlers = new ReferralHandlers();
    $this->assertEquals(TRUE, $referral_handlers->validCode('some-new-code'));
  }

}