<?php

namespace Drupal\Tests\healthz\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base functional test class for healthz tests.
 */
abstract class FunctionalTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'healthz_test_plugin',
  ];

  /**
   * An array of permissions to grant the user to test with.
   *
   * @var array
   */
  protected $permissions = [
    'access healthz checks',
  ];

  /**
   * An array of permissions to grant the user to administer settings with.
   *
   * @var array
   */
  protected $adminPermissions = [
    'administer healthz settings',
  ];

  /**
   * An user to view the healthz checks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $checkUser;

  /**
   * An user to administer the healthz checks.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Our config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->checkUser = $this->drupalCreateUser($this->permissions);
    $this->adminUser = $this->drupalCreateUser($this->adminPermissions);
    $this->config = \Drupal::configFactory()->getEditable('healthz.settings');
  }

  /**
   * Check that a checkbox is checked.
   *
   * @see https://www.drupal.org/node/2905019
   */
  public function assertCheckboxChecked($id) {
    $this->assertTrue($this->assertSession()->fieldExists($id)->hasAttribute('checked'), sprintf('Checkbox "%s" is not checked, but it should be.', $id));
  }

  /**
   * Check that a checkbox is not checked.
   *
   * @see https://www.drupal.org/node/2905019
   */
  public function assertCheckboxNotChecked($id) {
    $this->assertFalse($this->assertSession()->fieldExists($id)->hasAttribute('checked'), sprintf('Checkbox "%s" is checked, but it should not be.', $id));
  }

}
