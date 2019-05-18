<?php

namespace Drupal\Tests\regcode_simple\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test "plain text" -registration code.
 *
 * @group regcode_simple
 */
class RegcodeSimpleCodePlainTextTest extends BrowserTestBase {

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
    $perms = [
      'access administration pages',
      'administer account settings',
      'administer users',
    ];
    $admin = $this->drupalCreateUser($perms);
    $this->drupalLogin($admin);
    $this->drupalGet('admin/config/people/accounts');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Registration code');

    // Set the code, submit the form.
    // Set Registration -value to Visitor to allow code usage.
    $this->getSession()->getPage()->fillField('user_register', 'visitors');
    $this->getSession()->getPage()->fillField('regcode_type', 'code_plain_text');
    $this->getSession()->getPage()->fillField('code_plain_text', "somecode\nsome other code");
    $this->getSession()->getPage()->pressButton('Save configuration');

    // Assert: configuration saved successfully.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // As an admin, create a new user account. The registration code
    // is not needed and should not be displayed.
    $this->drupalGet('admin/people/create');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Add user');
    $this->assertSession()->fieldNotExists('regcode_simple');

    $this->getSession()->getPage()->fillField('mail', 'new_user_set_by_admin@local.host');
    $this->getSession()->getPage()->fillField('name', 'new.user.set.by.admin');
    $this->getSession()->getPage()->fillField('pass[pass1]', "PassWord/1234");
    $this->getSession()->getPage()->fillField('pass[pass2]', "PassWord/1234");
    $this->getSession()->getPage()->pressButton('Create new account');

    // Assert: new user account saved successfully.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Created a new user account for new.user.set.by.admin.');

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
    $this->getSession()->getPage()->fillField('regcode_simple', 'invalid regcode');
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
    $this->getSession()->getPage()->fillField('regcode_simple', 'somecode');
    $this->getSession()->getPage()->pressButton('Create new account');

    // Assert: Response contains text reflecting successful registration.
    $this->assertSession()->pageTextNotContains('Registration code field is required.');
    $this->assertSession()->pageTextNotContains('Registration code is not valid.');
    $this->assertSession()->pageTextContains('A welcome message with further instructions has been sent to your email address.');
  }

}
