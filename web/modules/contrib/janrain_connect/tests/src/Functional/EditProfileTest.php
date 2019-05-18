<?php

namespace Drupal\Tests\janrain_connect\Functional;

use Drupal\janrain_connect\Constants\JanrainConnectWebServiceConstants;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for Edit Profile.
 *
 * @group janrain_connect
 */
class EditProfileTest extends BrowserTestBase {

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
   * Tests for the Edit Profile form.
   */
  public function testEditProfile() {

    // Step 1: Access the page.
    $this->drupalGet('janrain/form/' . JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_EDIT_PROFILE . '/test');

    // Step 2: Fill all fields.
    $fields_to_test_registration = [
      'firstName' => 'First Name',
      'middleName' => 'Middle Name',
      'lastName' => 'Last Name',
      'mobile' => '+1 718-545-0100',
      'birthdate' => '26/04/1989',
      'displayName' => 'Display Name',
      'emailAddress' => 'jorge@gmail.com',
      'phone' => '+1 718-545-0100',
      'addressStreetAddress1' => 'Manhattan',
      'addressStreetAddress2' => 'Manhattan',
      'addressCity' => 'Campinas',
      'addressPostalCode' => '13100000',
    ];

    foreach ($fields_to_test_registration as $field_id => $value) {
      $this->getSession()->getPage()->fillField($field_id, $value);
    }

    // Step 3: Check the optIn checkbox.
    $this->getSession()->getPage()->checkField('optIn');

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
