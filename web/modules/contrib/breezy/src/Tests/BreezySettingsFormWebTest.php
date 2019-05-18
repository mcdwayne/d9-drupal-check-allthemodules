<?php

namespace Drupal\breezy\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Breezy settings form.
 *
 * @group breezy
 */
class BreezySettingsFormWebTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['breezy'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'administer breezy',
    ];
    $this->adminUser = $this->createUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test the settings page.
   */
  protected function testSettingsSections() {
    $this->drupalGet('/admin/config/services/breezy');
    $this->assertResponse(200);

    $this->assertNoDuplicateIds();

    $this->assertText(t('Account Settings'));
  }

  /**
   * Test account settings fields.
   */
  protected function testAccountSettingsFields() {
    $this->drupalGet('/admin/config/services/breezy');
    $this->assertFieldByName('breezy_company_id');
    $this->assertFieldByName('breezy_email');
    $this->assertFieldByName('breezy_password');
  }

  /**
   * Test settings form submission.
   */
  protected function testSettingsFormSubmission() {
    $this->drupalGet('/admin/config/services/breezy');
    $test_values = [
      'breezy_company_id' => 'QWERTY',
      'breezy_email' => 'admin@example.com',
      'breezy_password' => 'emetic-stan-masseuse-wiretap-anther',
    ];
    $this->drupalPostForm(NULL, $test_values, t('Save configuration'));
    $this->assertText('The configuration options have been saved.');
  }

}
