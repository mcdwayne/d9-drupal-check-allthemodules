<?php

namespace Drupal\Tests\packages\Kernel;

use Drupal\Tests\packages\Kernel\PackagesTestBase;

/**
 * Tests all available Packages hooks.
 *
 * @group packages
 */
class PackagesHooksTest extends PackagesTestBase {

  /**
   * Tests hook_packages_info_alter().
   */
  public function testPackagesInfoAlterHook() {
    // Get the definition of the test package plugin.
    $definition = $this->packages->getPackage('test')->getPluginDefinition();

    // Test that the test package label was overridden by the hook.
    $this->assertEquals($definition['label'], 'Packages test package');
  }

  /**
   * Tests hook_packages_states_alter().
   */
  public function testPackagesStatesAlterHook() {
    // Get the state of the test package.
    $state = $this->packages->getState('test');

    // Test that the test package state access was overridden by the hook
    // to now be set to TRUE. At this point there is no logged in user so the
    // access should be set to FALSE but the hook will override it.
    $this->assertTrue($state->hasAccess());
  }

}
