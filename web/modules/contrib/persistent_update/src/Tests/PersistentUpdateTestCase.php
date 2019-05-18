<?php

namespace Drupal\persistent_update\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Persistent Update API core functionality tests.
 *
 * @group Persistent Update API
 */
class PersistentUpdateTestCase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['persistent_update'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Set all installed modules, excluding Persistent Update API, to their
    // latest schema versions, as they have just been installed and should
    // already have this set to prevent unnecessary updates from attempting to
    // run. Persistent Update API modifies it's schema version on
    // hook_install().
    $modules = \Drupal::moduleHandler()->getModuleList();
    unset($modules['persistent_update']);
    /** @var \Drupal\Core\Extension\Extension $module */
    foreach ($modules as $module) {
      $schema_versions = drupal_get_schema_versions($module->getName());
      if (is_array($schema_versions)) {
        drupal_set_installed_schema_version($module->getName(), end($schema_versions));
      }
    }

    // Create user with 'administer software updates' and login.
    $user = $this->drupalCreateUser(['administer software updates']);
    $this->drupalLogin($user);
  }

  /**
   * Test persistent updates are run and persist.
   */
  public function testPersistentUpdate() {
    // Ensure Persistent Update API schema is set to 8100 after install.
    $schema_version = drupal_get_installed_schema_version('persistent_update');
    $this->assertEqual($schema_version, 8100, 'Persistent Update API schema is set at 8000 after install.');

    // Ensure Persistent Update API update is available to be run.
    $this->drupalGet('/update.php/selection');
    $this->assertText('persistent_update module', 'Persistent Update API module update available.');

    // Ensure Persistent Update API update run successfully.
    $this->drupalGet('/update.php/run');
    $this->assertText('Updates were attempted.', 'Persistent Update API module updates run.');

    // Ensure Persistent Update API schema is set to 8100 after updates.
    $schema_version = drupal_get_installed_schema_version('persistent_update');
    $this->assertEqual($schema_version, 8100, 'Persistent Update API schema is set at 8000 after updates.');

    // Ensure Persistent Update API update is still available to be run.
    $this->drupalGet('/update.php/selection');
    $this->assertText('persistent_update module', 'Persistent Update API module update still available.');
  }

}
