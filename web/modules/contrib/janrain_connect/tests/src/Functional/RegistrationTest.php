<?php

namespace Drupal\Tests\janrain_connect\Functional;

use Drupal\janrain_connect\Constants\JanrainConnectWebServiceConstants;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for Registration.
 *
 * @group janrain_connect
 */
class RegistrationTest extends BrowserTestBase {

  /**
   * Array with projects.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'block',
    'user',
    'janrain_connect',
    'janrain_connect_ui',
    'janrain_connect_block',
    'janrain_connect_validate',
  ];

  /**
   * Profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Tests for the Registration form.
   */
  public function testRegistration() {

    // Step 1: Access the page.
    $this->drupalGet('janrain/form/' . JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_REGISTRATION . '/test');

    // Step 2: Fill all fields.
    $fields_to_test_registration = [
      'firstName' => 'First Name',
      'middleName' => 'Middle Name',
      'lastName' => 'Last Name',
      'emailAddress' => 'jorge@gmail.com',
      'displayName' => 'Display Name',
      'newPassword' => 'password',
      'newPasswordConfirm' => 'password',
      'mobile' => '+1 718-545-0100',
      'birthdate' => '26/04/1989',
      'phone' => '+1 718-545-0100',
      'addressStreetAddress1' => 'Manhattan',
      'addressStreetAddress2' => 'Manhattan',
      'addressCity' => 'Campinas',
      'addressPostalCode' => '13100000',
    ];

    foreach ($fields_to_test_registration as $field_id => $value) {
      $this->getSession()->getPage()->fillField($field_id, $value);
    }

    // Step 3: Check the optInRegistration checkbox.
    $this->getSession()->getPage()->checkField('optInRegistration');

    // Step 4: Selection option for gender.
    $this->getSession()->getPage()->selectFieldOption('gender', 'male');

    // Step 5: Selection option for addressState.
    $this->getSession()->getPage()->selectFieldOption('addressState', 'NY');

    // Step 6: Selection option for addressCountry.
    $this->getSession()->getPage()->selectFieldOption('addressCountry', 'BR');

    // Step 7: Look on the page for the 'Submit' button.
    $this->assertSession()->buttonExists('edit-submit');
  }

}
