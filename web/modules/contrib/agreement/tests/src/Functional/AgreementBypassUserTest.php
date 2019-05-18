<?php

namespace Drupal\Tests\agreement\Functional;

/**
 * Tests the bypass agreement permission.
 *
 * @group agreement
 */
class AgreementBypassUserTest extends AgreementTestBase {

  /**
   * Asserts no agreement for a user with the "bypass agreement" permission.
   */
  public function testAgreement() {
    $account = $this->drupalCreateUser(['bypass agreement', 'access content']);
    $this->drupalLogin($account);
    $this->assertNotAgreementPage($this->agreement);
  }

}
