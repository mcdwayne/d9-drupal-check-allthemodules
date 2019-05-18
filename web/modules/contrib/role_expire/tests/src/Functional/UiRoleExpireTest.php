<?php

namespace Drupal\Tests\role_expire\Functional;


/**
 * Tests that the Role expire interface is available.
 *
 * @code
 * vendor/bin/phpunit ../modules/role_expire/tests/src/Functional/UiRoleExpireTest.php
 * @endcode
 *
 * @group role_expire
 *
 * Remember: each test run on a separate Drupal instance.
 *
 * https://www.drupal.org/docs/8/testing/types-of-tests-in-drupal-8
 * https://www.drupal.org/docs/8/phpunit/phpunit-browser-test-tutorial
 * http://www.pixelite.co.nz/article/writing-phpunit-tests-for-your-custom-modules-in-drupal-8/
 * https://www.drupal.org/docs/8/api/configuration-api/configuration-schemametadata
 * https://api.drupal.org/api/drupal/vendor%21behat%21mink%21src%21Element%21DocumentElement.php/class/DocumentElement/8.6.x
 * The rules module has a lot of tests that can serve as an example. Example: UiPageTest.php
 */
class UiRoleExpireTest extends RoleExpireBrowserTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['role_expire'];

  /**
   * Tests that users can assign a default role after a role expires.
   */
  public function testRoleExpireAdminPage() {
    $account = $this->drupalCreateUser(['administer role expire']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/system/role-expire');
    $this->assertSession()->statusCodeEquals(200);

    // Test that we can set default roles after a roles expires.
    $this->assertSession()->pageTextContains('Role to assign after the role');
  }

  /**
   * Tests that users can assign an expiration date/time on user roles.
   */
  public function testRoleExpireEditUserFields() {
    $account = $this->drupalCreateUser(['administer role expire']);
    $this->drupalLogin($account);

    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // Test that we can set expiration for user roles.
    $this->assertSession()->pageTextContains('role expiration date/time');
  }

  /**
   * Tests that users can assign a default expiration date/time on roles.
   */
  public function testRoleExpireEditRoleFields() {
    $account = $this->drupalCreateUser(['administer permissions', 'administer role expire']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/people/roles/manage/anonymous');
    $this->assertSession()->statusCodeEquals(200);

    // Test that the default duration field is available.
    $this->assertSession()->pageTextContains('Default duration for the role');
  }

  /**
   * Tests that users can assign default roles to assign after each role expires.
   */
  public function testRoleExpireAdminPageAction() {
    $account = $this->drupalCreateUser(['administer permissions', 'administer role expire']);
    $this->drupalLogin($account);

    // Create two roles.
    $this->createRoleWithOptionalExpirationUI('test role', 'test_role');
    $this->createRoleWithOptionalExpirationUI('test role two', 'test_role_two');

    // We assign a role to assign after each role expires.
    $test_def = 'test_role_two';
    $test_two_def = 'test_role';
    $this->drupalGet('admin/config/system/role-expire');
    $this->getSession()->getPage()->selectFieldOption('edit-test-role', $test_def);
    $this->getSession()->getPage()->selectFieldOption('edit-test-role-two', $test_two_def);
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/config/system/role-expire');
    $this->assertSession()->statusCodeEquals(200);

    $stored_value = $this->getSession()->getPage()->findField('edit-test-role')->getValue();
    $this->assertEquals($test_def, $stored_value);

    $stored_value = $this->getSession()->getPage()->findField('edit-test-role-two')->getValue();
    $this->assertEquals($test_two_def, $stored_value);
  }

  /**
   * Tests that we can add a role with default expiration and assign it to a new user.
   */
  public function testRoleExpireEditUserFieldsAction() {
    $account = $this->drupalCreateUser(['administer permissions', 'administer role expire']);
    $this->drupalLogin($account);

    // Create a role with an expiration date.
    $value_to_store = '2 days';
    $this->createRoleWithOptionalExpirationUI('test role', 'test_role', $value_to_store);

    // Assign the role to that user.
    $this->drupalGet('user/' . $account->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->getSession()->getPage()->checkField('edit-roles-test-role');
    $this->getSession()->getPage()->pressButton('Save');

    // Check that the user has the role and it has the default expiration date.
    $expected_date = date('Y-m-d', strtotime('2 days'));
    $this->drupalGet('user/' . $account->id() . '/edit');
    $stored_value = $this->getSession()->getPage()->findField('edit-role-expire-test-role')->getValue();
    $stored_date = substr($stored_value, 0, 10);
    $this->assertEquals($expected_date, $stored_date);

    // Test that we can set expiration for user roles.
    $this->assertSession()->pageTextContains('role expiration date/time');
  }

  /**
   * Tests that users can create a role with default expiration date/time.
   */
  public function testRoleExpireEditRoleFieldsAction() {
    $account = $this->drupalCreateUser(['administer permissions', 'administer role expire']);
    $this->drupalLogin($account);

    $value_to_store = '2 days';
    $this->createRoleWithOptionalExpirationUI('test role', 'test_role', $value_to_store);

    $this->drupalGet('admin/people/roles/manage/test_role');
    $this->assertSession()->statusCodeEquals(200);

    $stored_value = $this->getSession()->getPage()->findField('Default duration for the role')->getValue();
    $this->assertEquals($value_to_store, $stored_value);
  }

}
