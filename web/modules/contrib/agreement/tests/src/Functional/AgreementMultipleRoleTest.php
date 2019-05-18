<?php

namespace Drupal\Tests\agreement\Functional;

/**
 * Tests agreement that applies to multiple roles.
 *
 * @group agreement
 */
class AgreementMultipleRoleTest extends AgreementTestBase {

  /**
   * First required role ID.
   *
   * @var string
   */
  protected $requiredRole;

  /**
   * Second required role ID.
   *
   * @var string
   */
  protected $requiredSecondRole;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create the roles.
    $this->requiredRole = $this->createRole(['access content']);
    $this->requiredSecondRole = $this->createRole(['access content', 'create page content']);

    // Set the agreement to use the roles from the two required users.
    $settings = $this->agreement->getSettings();
    $settings['roles'] = [$this->requiredRole, $this->requiredSecondRole];
    $this->agreement->set('settings', $settings);
    $this->agreement->save();

    $this->assertEquals($settings['roles'], $this->agreement->getSettings()['roles']);
  }

  /**
   * Asserts that the user with the first role gets the agreement page.
   */
  public function testAgreementForFirstRole() {
    // Create the user account.
    $requiredUser = $this->createUnprivilegedUser();
    $requiredUser->addRole($this->requiredRole);
    $requiredUser->save();

    // Log in as the user.
    $this->drupalLogin($requiredUser);

    // Sent to agreement page.
    $this->assertAgreementPage($this->agreement);

    // Go to front page, no agreement.
    $this->drupalGet('/node');
    $this->assertNotAgreementPage($this->agreement);

    // Go anywhere else, open agreement.
    $this->drupalGet('/user/' . $requiredUser->id());
    $this->assertAgreementPage($this->agreement);

    // Try submitting agreement form.
    $this->assertNotAgreed($this->agreement);
    $this->assertAgreementPage($this->agreement);
  }

  /**
   * Asserts that the user with the second role gets the agreement page.
   */
  public function testAgreementForSecondRole() {
    // Create the user account.
    $requiredUser = $this->createUnprivilegedUser();
    $requiredUser->addRole($this->requiredSecondRole);
    $requiredUser->save();

    // Log in as the user.
    $this->drupalLogin($requiredUser);

    // Sent to agreement page.
    $this->assertAgreementPage($this->agreement);

    // Go to front page, no agreement.
    $this->drupalGet('/node');
    $this->assertNotAgreementPage($this->agreement);

    // Go anywhere else, open agreement.
    $this->drupalGet('/user/' . $requiredUser->id());
    $this->assertAgreementPage($this->agreement);

    // Try submitting agreement form.
    $this->assertNotAgreed($this->agreement);
    $this->assertAgreementPage($this->agreement);
  }

  /**
   * Asserts that user does not get agreement page without those roles.
   */
  public function testNoAgreementWithoutRole() {
    // Create the user account.
    $unprivilegedUser = $this->createUnprivilegedUser();

    // Log in as the user, no agreement.
    $this->drupalLogin($unprivilegedUser);
    $this->assertNotAgreementPage($this->agreement);

    // Go to front page, no agreement.
    $this->drupalGet('/node');
    $this->assertNotAgreementPage($this->agreement);

    // Go anywhere else, no agreement.
    $this->drupalGet('/user/' . $unprivilegedUser->id());
    $this->assertNotAgreementPage($this->agreement);
  }

}
