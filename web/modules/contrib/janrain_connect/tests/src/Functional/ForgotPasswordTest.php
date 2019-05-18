<?php

namespace Drupal\Tests\janrain_connect\Functional;

use Drupal\janrain_connect\Constants\JanrainConnectWebServiceConstants;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for Forgot Password.
 *
 * @group janrain_connect
 */
class ForgotPasswordTest extends BrowserTestBase {

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
   * Tests for the Forgot Password form.
   */
  public function testForgotPassword() {

    // Step 1: Access the page.
    $this->drupalGet('janrain/form/' . JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_FORGOT_PASSWORD . '/test');

    // Step 2: Fill signInEmailAddress field.
    $this->getSession()->getPage()->fillField('signInEmailAddress', 'email@gmail.com');

    // Step 3: Look on the page for the 'Submit' button.
    $this->assertSession()->buttonExists('edit-submit');
  }

}
