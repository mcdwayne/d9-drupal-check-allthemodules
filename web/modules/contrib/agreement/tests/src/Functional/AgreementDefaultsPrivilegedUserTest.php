<?php

namespace Drupal\Tests\agreement\Functional;

/**
 * Tests the default agreement with a privileged user.
 *
 * @group agreement
 */
class AgreementDefaultsPrivilegedUserTest extends AgreementTestBase {

  /**
   * Asserts that the default settings work for the admin user.
   */
  public function testAgreement() {
    $account = $this->createPrivilegedUser();
    $this->drupalLogin($account);

    // After save, re-open agreement settings.
    $this->drupalGet('admin/config/people/agreement/manage/default');
    $this->assertSession()->titleEquals('Manage Agreement: Default agreement | Drupal');

    // Go to front page, no agreement.
    $this->drupalGet('/node');
    $this->assertNotAgreementPage($this->agreement);

    // Go anywhere else, open agreement.
    $this->drupalGet('/admin');
    $this->assertAgreementPage($this->agreement);

    // Try to go somewhere without submitting.
    $this->drupalGet('/node/add');
    $this->assertAgreementPage($this->agreement);

    // Try submitting agreement form.
    $this->assertNotAgreed($this->agreement);
    $this->assertAgreed($this->agreement);

    $this->drupalGet('/admin');
    $this->assertNotAgreementPage($this->agreement);
  }

}
