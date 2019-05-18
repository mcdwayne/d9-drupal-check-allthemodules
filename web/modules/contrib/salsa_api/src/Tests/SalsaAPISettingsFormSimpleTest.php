<?php

namespace Drupal\salsa_api\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the SalsaAPI settings form.
 *
 * @group salsa
 */
class SalsaAPISettingsFormSimpleTest extends WebTestBase {

  /**
   * @var string
   */
  protected $correctUrl = 'https://example.com';

  /**
   * @var string
   */
  protected $correctUsername = 'user@example.com';

  /**
   * @var string
   */
  protected $correctPassword = 'Correct password';

  /**
   * @var string
   */
  protected $wrongUrl = 'https://error.example.com';

  /**
   * @var string
   */
  protected $wrongUsername = 'error@example.com';

  /**
   * @var string
   */
  protected $wrongPassword = 'Wrong password';

  /**
   * @var int
   */
  protected $queryTimeout = 10;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('salsa_api', 'salsa_api_mock');

  /**
   * Configures test base and executes test cases.
   */
  public function testSalsaAPISettingsForm() {
    // Create and log in our user.
    $admin_user = $this->drupalCreateUser(array(
      'administer salsa',
    ));
    $this->drupalLogin($admin_user);

    $this->doTestSalsaSettingsFormOK();
    $this->doTestSalsaSettingsFormAuthenticationFailed();
    $this->doTestSalsaSettingsFormWrongURL();
  }

  /**
   * Case 1: Login successful.
   */
  public function doTestSalsaSettingsFormOK() {
    $edit = array(
      'url' => $this->correctUrl,
      'username' => $this->correctUsername,
      'password' => $this->correctPassword,
      'query_timeout' => $this->queryTimeout,
    );
    $this->drupalPostForm('admin/config/services/salsa', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));
  }

  /**
   * Case 2: Login failed, incorrect password and/or username.
   */
  public function doTestSalsaSettingsFormAuthenticationFailed() {
    $edit = array(
      'url' => $this->correctUrl,
      'username' => $this->wrongUsername,
      'password' => $this->wrongPassword,
      'query_timeout' => $this->queryTimeout,
    );
    $this->drupalPostForm('admin/config/services/salsa', $edit, t('Save configuration'));
    $this->assertText(t('Username and/or password incorrect.'));
  }

  /**
   * Case 3: 404 page / server down / any other error.
   */
  public function doTestSalsaSettingsFormWrongURL() {
    $edit = array(
      'url' => $this->wrongUrl,
      'username' => $this->correctUsername,
      'password' => $this->correctPassword,
      'query_timeout' => $this->queryTimeout,
    );
    $this->drupalPostForm('admin/config/services/salsa', $edit, t('Save configuration'));
    $this->assertText(t('This page is not available, please type in a correct URL or try again later.'));
  }

}
