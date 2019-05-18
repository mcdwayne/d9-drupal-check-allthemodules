<?php

namespace Drupal\Tests\agreement\Functional;

/**
 * Tests agreement when user does not have privileges.
 *
 * @group agreement
 */
class AgreementDefaultsUnprivilegedUserTest extends AgreementTestBase {

  /**
   * Asserts that the default settings work for the end user.
   */
  public function testAgreement() {
    $account = $this->createUnprivilegedUser();
    $this->drupalLogin($account);

    // Sent to agreement page.
    $this->assertAgreementPage($this->agreement);

    // Go to front page, no agreement.
    $this->drupalGet('/node');
    $this->assertNotAgreementPage($this->agreement);

    // Go anywhere else, open agreement.
    $this->drupalGet('/user/' . $account->id());
    $this->assertAgreementPage($this->agreement);

    // Try submitting agreement form.
    $this->assertNotAgreed($this->agreement);
    $this->assertAgreementPage($this->agreement);

    $this->drupalGet('/admin/config/people/agreement');
    $this->assertSession()->statusCodeEquals(403);
  }

}
