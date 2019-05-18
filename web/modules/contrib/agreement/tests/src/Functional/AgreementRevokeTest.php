<?php

namespace Drupal\Tests\agreement\Functional;

/**
 * Tests the "revoke agreement" functionality.
 *
 * @group agreement
 */
class AgreementRevokeTest extends AgreementTestBase {

  /**
   * Asserts that an user that has agreed can revoke the agreement.
   */
  public function testAgreement() {
    $account = $this->createRevokeUser();
    $this->drupalLogin($account);

    // Agree to the agreement on presented after login.
    $this->assertAgreed($this->agreement);
    $this->assertNotAgreementPage($this->agreement);

    // Cancel the agreement.
    $settings = $this->agreement->getSettings();
    $edit = [
      'agree' => '0',
    ];
    $this->drupalPostForm('/agreement', $edit, $settings['submit']);
    $this->assertSession()->pageTextContains('You have successfully revoked your acceptance of our agreement. ');

    // Assert agreement page after revoking.
    $this->drupalGet('/node/' . $this->node->id());
    $this->assertAgreementPage($this->agreement);

    // Assert that the user can re-accept the agreement.
    $this->assertAgreed($this->agreement);
    $this->assertNotAgreementPage($this->agreement);
  }

}
