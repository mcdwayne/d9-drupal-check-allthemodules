<?php

namespace Drupal\Tests\packages\Kernel;

use Drupal\Tests\packages\Kernel\PackagesTestBase;
use Drupal\user\Entity\Role;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Tests the Packages service.
 *
 * @group packages
 */
class PackagesTest extends PackagesTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
    'packages',
    'packages_test',
    'packages_example_login_greeting',
    'packages_example_page',
  ];

  /**
   * The user used in test.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $packagesUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('packages', 'packages');

    // Create user 1 and disregard because we do not want to test with an admin.
    $this->createUser();

    // Create a role that allows access to packages but not the example page
    // package.
    $role = Role::create([
      'id' => 'packages',
      'permissions' => ['access packages'],
    ]);
    $role->save();

    // Create a test user.
    $this->packagesUser = $this->createUser($role->id());
  }

  /**
   * Tests package plugin annotations.
   */
  public function testPluginAnnotation() {
    // Get the test package plugin definition.
    $definition = $this->packages->getPackage('test')->getPluginDefinition();

    // List the expected annotations.
    $annotations = [
      'id',
      'label',
      'description',
      'enabled',
      'configurable',
      'permission',
      'default_settings',
    ];

    // Iterate the annotations.
    foreach ($annotations as $key) {
      // Make sure the annontation is present.
      $this->assertTrue(isset($definition[$key]));
    }
  }

  /**
   * Tests getting the states.
   */
  public function testGetStates() {
    // Get the states.
    $states = $this->packages->getStates();

    // Three states should be returned.
    $this->assertEquals(count($states), 3);

    // Make sure the state is a state.
    $this->assertEquals(get_class($states['test']), 'Drupal\packages\PackageState');
  }

  /**
   * Tests the rebuilding of states.
   */
  public function testRebuildStates() {
    // Revoke access to the test package.
    $this->packages->getState('test')->setAccess(FALSE);

    // Grant access to the login greeting package.
    $this->packages->getState('login_greeting')->setAccess(TRUE);

    // Rebuild the states.
    $this->packages->buildStates();

    // Access should have been rechecked.
    $this->assertTrue($this->packages->getState('test')->hasAccess());
    $this->assertFalse($this->packages->getState('login_greeting')->hasAccess());
  }

  /**
   * Tests the population of default settings.
   */
  public function testDefaultSettings() {
    // Get the login greeting package state settings.
    $settings = $this->packages->getState('login_greeting')->getSettings();

    // Make sure the default settings were loaded.
    $this->assertTrue(isset($settings['show_datetime']));
    $this->assertTrue($settings['show_datetime']);
  }

  /**
   * Tests invalid packages.
   */
  public function testInvalidPackages() {
    try {
      $this->packages->getState('abcdef');
      $this->assertTrue(FALSE);
    }
    catch (PluginNotFoundException $e) {
      $this->assertTrue(TRUE);
    }

    try {
      $this->packages->getPackage('abcdef');
      $this->assertTrue(FALSE);
    }
    catch (PluginNotFoundException $e) {
      $this->assertTrue(TRUE);
    }
  }

  /**
   * Tests the anonymous package state.
   */
  public function testAnonymousState() {
    // Packages should be force disabled and access restricted.
    $this->assertFalse($this->packages->getState('login_greeting')->isEnabled());
    $this->assertFalse($this->packages->getState('login_greeting')->hasAccess());
  }

  /**
   * Tests the authenticated package state.
   */
  public function testAuthenticatedState() {
    // Log in.
    $this->logIn($this->packagesUser);

    // Rebuild the states.
    $this->packages->buildStates();

    // Login greeting package should be enabled by default and accessible.
    $this->assertTrue($this->packages->getState('login_greeting')->isEnabled());
    $this->assertTrue($this->packages->getState('login_greeting')->hasAccess());

    // Example page package should be disabled by default and inaccessible.
    $this->assertFalse($this->packages->getState('example_page')->isEnabled());
    $this->assertFalse($this->packages->getState('example_page')->hasAccess());
  }

  /**
   * Tests saving package states.
   */
  public function testSavingStates() {
    // Log in.
    $this->logIn($this->packagesUser);

    // Rebuild the states.
    $this->packages->buildStates();

    // Disable the login greeting package.
    $this->packages->getState('login_greeting')->disable();

    // Save the packages.
    $this->packages->saveStates();

    // Rebuild the states again.
    $this->packages->buildStates();

    // The package should remain disabled.
    $this->assertFalse($this->packages->getState('login_greeting')->isEnabled());
  }

}
