<?php

namespace Drupal\Tests\config_actions\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * test the ConfigActions module
 *
 * @group config_actions
 */
class ConfigActionsModuleTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'system',
    'user',
    'file',
    'image',
    'config_actions',
    'test_config_actions'
  ];

  /**
   * Prevent strict schema errors during test.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * @var \Drupal\config_actions\ConfigActionsService
   */
  protected $configActions;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->configActions = \Drupal::service('config_actions');
    parent::installConfig(['system']);
    config_actions_modules_installed(['test_config_actions']);
  }

  /**
   * Helper function to load a specific configuration item
   * @param string $id
   * @return array of config data
   */
  protected function getConfig($id) {
    return \Drupal::service('config.factory')->get($id)->get();
  }

  /**
   * Test enabling module.
   */
  public function testEnable() {
    // Test that the field storage config got created.
    $field_storage = $this->getConfig('field.storage.node.myproject_image');
    $this->assertEqual('myproject_image', $field_storage['field_name'], 'Field storage has correct field_name.');
    $this->assertEqual('node.myproject_image', $field_storage['id'], 'Field storage has correct id.');

    // Test that the field instance config got created.
    $field_instance = $this->getConfig('field.field.node.article.myproject_image');
    $this->assertEqual('myproject_image', $field_instance['field_name'], 'Field instance has correct field base.');
    $this->assertEqual('node.article.myproject_image', $field_instance['id'], 'Field instance has correct id.');
    $this->assertEqual(['field.storage.node.myproject_image', 'node.type.article'], $field_instance['dependencies']['config'],
      'Field instance has correct config dependencies.');

    // Test that creating a article node actually has this new field.
    // Cannot do this from KernelTest.
    //$node = $this->drupalCreateNode(array('type' => 'article'));
    //$this->assertEqual('myproject_image', $node->myproject_image->getName(), 'Article node has correct image field.');

    // Test the override of the short date label.
    $date_config = $this->getConfig('core.date_format.short');
    $this->assertEqual('Test short date', $date_config['label']);

  }

}
