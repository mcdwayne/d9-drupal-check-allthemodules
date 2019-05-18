<?php

namespace Drupal\Tests\config_actions\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Serialization\Yaml;
use Drupal\config_actions\ConfigActionsTransform;

/**
 * test the ConfigActions service
 *
 * @coversDefaultClass Drupal\config_actions\ConfigActionsService
 * @group config_actions
 */
class ConfigActionsServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'system',
    'user',
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
    $this->installConfig('system');
    $this->installConfig('node');
    $this->installConfig('test_config_actions');
    $this->configActions = $this->container->get('config_actions');
  }

  /**
   * Helper function to load a specific configuration item
   * @param string $id
   * @return array of config data
   */
  protected function getConfig($id) {
    return $this->container->get('config.factory')->get($id)->get();
  }

  /**
   * @covers ::listAll
   * @covers ::listActions
   */
  public function testListAll() {
    // Test listing all modules with actions.
    $list = $this->configActions->listAll();
    self::assertNotNull($list['test_config_actions'], $list);

    // Test listing specific modules with actions.
    $list = $this->configActions->listAll('test_config_actions');
    self::assertArrayHasKey('test_config_actions', $list);
    self::assertArrayHasKey('field_template_action', $list['test_config_actions']);
    self::assertArrayHasKey('core.date_format.short', $list['test_config_actions']);

    // Test a module that doesn't have any actions of its own.
    $list = $this->configActions->listAll('config_actions');
    self::assertArrayNotHasKey('config_actions', $list);

    // Test listing specific file in modules with actions.
    $list = $this->configActions->listAll('test_config_actions', 'field_template_action');
    self::assertArrayHasKey('test_config_actions', $list);
    self::assertArrayHasKey('field_template_action', $list['test_config_actions']);
    // Shouldn't see actions in a different file
    self::assertArrayNotHasKey('core.date_format.short', $list['test_config_actions']);

    // Look at return data for specific actions and sub-actions
    $action_names = array_keys($list['test_config_actions']['field_template_action']);
    self::assertEquals(['field_storage', 'field_instance:article', 'field_instance:page'], $action_names);
  }

  /**
   * @covers ::autoExecute
   */
  public function testAutoExecute() {
    // Test default value returned.
    self::assertFalse($this->configActions->autoExecute());

    $source = 'core.date_format.long';
    $value = 'My new label';
    $action = [
      'plugin' => 'change',
      'auto' => FALSE,
      'source' => $source,
      'path' => ['label'],
      'value' => $value,
    ];

    $orig_config = $this->getConfig($source);
    $orig_config['label'] = $value;

    // First test with autoExecute disabled.
    $this->configActions->autoExecute(FALSE);
    self::assertFalse($this->configActions->autoExecute());

    $new_config = $this->configActions->processAction($action);
    self::assertEquals($orig_config, $new_config);

    // Next, test with autoExecute enabled.
    // Action should get skipped.
    $this->configActions->autoExecute(TRUE);
    self::assertTrue($this->configActions->autoExecute());

    $new_config = $this->configActions->processAction($action);
    self::assertNull($new_config);
  }

}
