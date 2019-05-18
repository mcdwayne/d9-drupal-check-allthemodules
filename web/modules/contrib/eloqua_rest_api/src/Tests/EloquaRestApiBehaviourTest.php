<?php
namespace Drupal\eloqua_rest_api\Tests;

/**
 * Behavioral tests for the Eloqua REST API module.
 *
 * @group eloqua_rest_api
 */
class EloquaRestApiBehaviourTest extends \Drupal\simpletest\WebTestBase {

  protected $profile = 'standard';
  public static $modules = ['eloqua_rest_api'];

  public static function getInfo() {
    return [
      'name' => 'Eloqua REST API behavioral tests',
      'description' => 'Behavioral tests for the Eloqua REST API module.',
      'group' => 'Eloqua',
    ];
  }

  /**
   * Ensure only those with permissions have access to the configuration page.
   */
  public function testShouldFollowPermissions() {
    // Create an admin user without proper credentials.
    $account = $this->drupalCreateUser([
      'access administration pages'
      ]);
    $this->drupalLogin($account);

    // Attempt to visit the configuration page.
    $this->drupalGet('admin/config/services/eloqua');
    $this->assertNoFieldByName('eloqua_rest_api_site_name', NULL, 'Did not find the configuration form.');
    $this->assertEqual($this->drupalGetHeader(':status'), 'HTTP/1.1 403 Forbidden', 'Proper permissions enforced.');

    // Log the user out.
    $this->drupalLogout();

    // Create an admin user WITH proper credentials.
    $account = $this->drupalCreateUser([
      'access administration pages',
      'administer eloqua rest api',
    ]);
    $this->drupalLogin($account);

    // Attempt to visit the configuration page.
    $this->drupalGet('admin/config/services/eloqua');
    $this->assertFieldByName('eloqua_rest_api_site_name', NULL, 'Found the configuration form.');
  }

  public function testShouldAllowConfigThroughUI() {
    // Create an admin user with proper credentials.
    $account = $this->drupalCreateUser([
      'access administration pages',
      'administer eloqua rest api',
    ]);
    $this->drupalLogin($account);

    // Attempt to fill out the config form with no values.
    $this->drupalPostForm('admin/config/services/eloqua', [], 'Save configuration');

    // Ensure all fields are required.
    $this->assertText('Site name field is required.', 'Site name field required');
    $this->assertText('Login name field is required.', 'Login field required.');
    $this->assertText('Login password field is required.', 'Password field required.');

    // Fill out the form with valid values.
    $this->drupalPostForm('admin/config/services/eloqua', [
      'eloqua_rest_api_site_name' => 'Example.Company',
      'eloqua_rest_api_login_name' => 'Foo',
      'eloqua_rest_api_login_password' => 'Bar123',
      'eloqua_rest_api_timeout' => 20,
    ], 'Save configuration');

    // Ensure our provided values were saved properly.
    $this->assertText('The configuration options have been saved.', 'Confirmation message shown.');
    $this->assertFieldByName('eloqua_rest_api_site_name', 'Example.Company', 'Saved the site name.');
    $this->assertFieldByName('eloqua_rest_api_login_name', 'Foo', 'Saved the login name.');
    $this->assertFieldByName('eloqua_rest_api_login_password', 'Bar123', 'Saved the password.');
    $this->assertFieldByName('eloqua_rest_api_timeout', 20, 'Saved the API timeout.');

    // Ensure provided values can be loaded via variable_get() as expected.
    $this->assertEqual(\Drupal::config('eloqua_rest_api.settings')->get('eloqua_rest_api_site_name'), 'Example.Company', 'Site name loaded programmatically.');
    $this->assertEqual(\Drupal::config('eloqua_rest_api.settings')->get('eloqua_rest_api_login_name'), 'Foo', 'Login name loaded programmatically.');
    $this->assertEqual(\Drupal::config('eloqua_rest_api.settings')->get('eloqua_rest_api_login_password'), 'Bar123', 'Password loaded programmatically.');
    $this->assertEqual(\Drupal::config('eloqua_rest_api.settings')->get('eloqua_rest_api_timeout'), 20, 'API timeout loaded programmatically.');
  }

}
