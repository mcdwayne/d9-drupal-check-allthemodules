<?php

/**
 * @file
 * Contains \Drupal\config_share\Tests\ConfigShareCreateTest.
 */

namespace Drupal\config_share\Tests;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\WebTestBase;

/**
 * Tests default configuration to be installed on demand.
 *
 * @group config_share
 */
class ConfigShareCreateTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array.
   */
  public static $modules = ['config', 'config_provider', 'config_share'];

  /**
   * Tests enabling the provider of the shared configuration first.
   */
  public function testInstallSharedFirst() {
    // Enable the test module that provides shared configuration, which
    // should be created only on demand.
    $this->installModule('config_share_test_shared');

    $node_type_id = 'config_share_test';
    $field_name = 'field_config_share_test_text';
    $field_storage_id = "node.$field_name";
    $field_id = "node.$node_type_id.$field_name";

    // Check that the content type does not exist yet.
    $node_type = NodeType::load($node_type_id);
    $this->assertFalse($node_type, 'The config_share_test content type was not created.');

    // Check that the field storage does not exist yet.
    $this->assertFalse(FieldStorageConfig::load($field_storage_id), 'The field storage was not created.');

    $this->uninstallModule('config_share_test_shared');

    // Enable the test module that provides configuration that requires shared
    // configuration.
    $this->installModule('config_share_test');

    // Check that the content type exists.
    $node_type = NodeType::load($node_type_id);
    $this->assertTrue($node_type, 'The config_share_test content type was created.');

    // Check that the field storage and field exist.
    $field_storage = FieldStorageConfig::load($field_storage_id);
    $this->assertTrue($field_storage, 'The field storage was created.');
    $field = FieldConfig::load($field_id);
    $this->assertTrue($field, 'The field was created.');
  }

  /**
   * Installs a module.
   *
   * @param string $module
   *   The module name.
   */
  protected function installModule($module) {
    $this->container->get('module_installer')->install(array($module));
    $this->container = \Drupal::getContainer();
  }

  /**
   * Uninstalls a module.
   *
   * @param string $module
   *   The module name.
   */
  protected function uninstallModule($module) {
    $this->container->get('module_installer')->uninstall(array($module));
    $this->container = \Drupal::getContainer();
  }

}
