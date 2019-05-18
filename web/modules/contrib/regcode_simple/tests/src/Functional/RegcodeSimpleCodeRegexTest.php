<?php

namespace Drupal\Tests\regcode_simple\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test "regular expression" -registration code.
 *
 * @group regcode_simple
 */
class RegcodeSimpleCodeRegexTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['regcode_simple'];

  /**
   * Make sure admin can read help text.
   */
  public function testCodePlainText() {

    // Login and go to config page.
    $admin = $this->drupalCreateUser(['access administration pages', 'administer account settings']);
    $this->drupalLogin($admin);
    $this->drupalGet('admin/config/people/accounts');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Registration code');

    // Set the code, submit the form.
    // Set Registration -value to Visitor to allow code usage.
    $this->getSession()->getPage()->fillField('user_register', 'visitors');
    $this->getSession()->getPage()->fillField('regcode_type', 'code_regex');
    $this->getSession()->getPage()->fillField('code_regex', "[a-c0-2]{7}");
    $this->getSession()->getPage()->pressButton('Save configuration');

    // Assert: configuration saved succesfully.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Go to the user registration page as anonymous user.
    $this->drupalLogout();
    $this->drupalGet('user/register');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Registration code');

    // Try to login without the code.
    $this->getSession()->getPage()->fillField('mail', 'user.without.code@local.host');
    $this->getSession()->getPage()->fillField('name', 'name.without.code');
    $this->getSession()->getPage()->pressButton('Create new account');

    // Assert: Response contains text reflecting a failed registration.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Registration code field is required.');
    $this->assertSession()->pageTextNotContains('Registration code is not valid.');
    $this->assertSession()->pageTextNotContains('A welcome message with further instructions has been sent to your email address.');

    // Try to login with an invalid code.
    $this->getSession()->getPage()->fillField('mail', 'user.with.invalid.code@local.host');
    $this->getSession()->getPage()->fillField('name', 'name.with.invalid.code');
    $this->getSession()->getPage()->fillField('regcode_simple', 'abcdefgh1234567890');
    $this->getSession()->getPage()->pressButton('Create new account');

    // Assert: Response contains text reflecting failing registration code
    // validation.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Registration code field is required.');
    $this->assertSession()->pageTextContains('Registration code is not valid.');
    $this->assertSession()->pageTextNotContains('A welcome message with further instructions has been sent to your email address.');

    // Try to login with the code.
    $this->getSession()->getPage()->fillField('mail', 'user.with.code@local.host');
    $this->getSession()->getPage()->fillField('name', 'name.with.code');
    $this->getSession()->getPage()->fillField('regcode_simple', 'abc0121');
    $this->getSession()->getPage()->pressButton('Create new account');

    // Assert: Response contains text reflecting successful registration.
    $this->assertSession()->pageTextNotContains('Registration code field is required.');
    $this->assertSession()->pageTextNotContains('Registration code is not valid.');
    $this->assertSession()->pageTextContains('A welcome message with further instructions has been sent to your email address.');
  }

}
