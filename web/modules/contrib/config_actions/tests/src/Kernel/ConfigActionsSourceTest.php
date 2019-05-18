<?php

namespace Drupal\Tests\config_actions\Kernel;

use Drupal\KernelTests\KernelTestBase;
use org\bovigo\vfs\vfsStream;
use Drupal\config_actions\Plugin\ConfigActionsSource\ConfigActionsFile;

/**
 * test the ConfigActionsSource plugins
 *
 * @group config_actions
 */
class ConfigActionsSourceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'config_actions',
    'test_config_actions',
  ];

  /**
   * Prevent strict schema errors during test.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The Source plugin manager.
   *
   * @var \Drupal\config_actions\ConfigActionsSourceManager
   */
  protected $sourceManager;

  public function setUp() {
    parent::setUp();
    $this->installConfig('system');
    $this->sourceManager = $this->container->get('plugin.manager.config_actions_source');
  }

  /**
   * @covers \Drupal\config_actions\Plugin\ConfigActionsSource\ConfigActionsArray
   */
  public function testArray() {
    $source = [
      'mykey' => 'test value'
    ];
    $options = [
      'source' => $source,
    ];
    /** @var \Drupal\config_actions\ConfigActionsSourceInterface $plugin */
    $plugin = $this->sourceManager->createInstance('array', $options);

    $this->assertTrue($plugin->detect($source), 'Source detected');
    $this->assertEquals($source, $plugin->load(), 'Loading plugin data');

    // First test merging new data
    $new_data = [
      'newkey' => 'new value'
    ];
    $source = array_merge($source, $new_data);
    $this->assertTrue($plugin->save($new_data), 'Saving data to plugin');
    $this->assertEquals($source, $plugin->load());

    // Next, test replacing existing data
    $new_data = [
      'mykey' => 'new value2',
      'newkey' => 'new value2',
    ];
    $source = $new_data;
    $this->assertTrue($plugin->save($new_data), 'Saving data to plugin');
    $this->assertEquals($source, $plugin->load());

    // Next, test without merge
    $new_data = [
      'newkey' => 'new value2',
    ];
    $source = $new_data;
    $plugin->setMerge(FALSE);
    $this->assertTrue($plugin->save($new_data), 'Saving data to plugin');
    $this->assertEquals($source, $plugin->load());
  }

  /**
   * @covers \Drupal\config_actions\Plugin\ConfigActionsSource\ConfigActionsId
   */
  public function testId() {
    $source = 'core.date_format.long';
    $options = [
      'source' => $source,
    ];
    /** @var \Drupal\config_actions\ConfigActionsSourceInterface $plugin */
    $plugin = $this->sourceManager->createInstance('id', $options);

    $this->assertTrue($plugin->detect($source), 'Source detected');
    $data = $plugin->load();
    $this->assertEquals('long', $data['id']);

    $new_data = [
      'label' => "My Label",
    ];
    $plugin->setMerge(TRUE);
    $this->assertTrue($plugin->save($new_data), 'Saving data to plugin');
    $data = $plugin->load();
    $this->assertEquals('My Label', $data['label']);
  }

  /**
   * @covers \Drupal\config_actions\Plugin\ConfigActionsSource\ConfigActionsId
   */
  public function testFile() {
    $source = 'field.field.node.image.yml';
    $options = [
      'source' => $source,
      'base' => DRUPAL_ROOT . '/' . drupal_get_path('module', 'test_config_actions'),

    ];
    /** @var \Drupal\config_actions\ConfigActionsSourceInterface $plugin */
    $plugin = $this->sourceManager->createInstance('file', $options);

    $this->assertTrue($plugin->detect($source), 'Source detected');
    $data = $plugin->load();
    $this->assertEquals('node.@bundle@.@field_name@', $data['id']);

    // Test file saving
    $path = DRUPAL_ROOT . drupal_get_path('module', 'test_config_actions');
    $config_file = 'myactions.yml';
    $filename = $path . '/' . $config_file;
    if (file_exists($filename)) {
      file_unmanaged_delete($filename);
    }

    // Now, write config data to the myactions.yml file
    $options = [
      'source' => $config_file,
      'base' => $path,
    ];
    $plugin = $this->sourceManager->createInstance('file', $options);
    $new_data = [
      'mykey' => "Test value",
    ];
    $this->assertTrue($plugin->save($new_data), 'Saving data to plugin');

    // Finally, read the data back from the file and see if it matches.
    $plugin = $this->sourceManager->createInstance('file', $options);
    $data = $plugin->load();
    $this->assertEquals($new_data, $data);

    // Next, perform the same test but instead of using the base_path, use
    // an absolute path in the source filename.
    $base_path = $path . '/' . drupal_get_path('module', 'test_config_actions');
    $config_file = 'myactions.yml';
    $options = [
      'source' => $base_path . '/' . $config_file,
    ];
    $plugin = $this->sourceManager->createInstance('file', $options);
    $new_data = [
      'mykey' => "Test value",
    ];
    $this->assertTrue($plugin->save($new_data), 'Saving data to plugin');

    // Finally, read the data back from the file and see if it matches.
    $plugin = $this->sourceManager->createInstance('file', $options);
    $data = $plugin->load();
    $this->assertEquals($new_data, $data);
  }

  /**
   * @covers \Drupal\config_actions\Plugin\ConfigActionsSource\ConfigActionsList
   */
  public function testList() {
    $source = [
      'field.field.node.image.yml',
      'core.date_format.long'
    ];
    $options = [
      'source' => $source,
      'base' => DRUPAL_ROOT . '/' . drupal_get_path('module', 'test_config_actions'),
    ];

    // First, test that the File is loaded first
    /** @var \Drupal\config_actions\ConfigActionsSourceInterface $plugin */
    $plugin = $this->sourceManager->createInstance('list', $options);

    $this->assertTrue($plugin->detect($source), 'Source detected');
    $data = $plugin->load();
    $this->assertEquals('node.@bundle@.@field_name@', $data['id']);

    // Next, test that when the file doesn't exist, the ID is loaded.
    $source = [
      'field.field.node.image.DOESNOTEXIST.yml',
      'core.date_format.long'
    ];
    $options = [
      'source' => $source,
      'base' => DRUPAL_ROOT . '/' . drupal_get_path('module', 'test_config_actions'),
    ];

    /** @var \Drupal\config_actions\ConfigActionsSourceInterface $plugin */
    $plugin = $this->sourceManager->createInstance('list', $options);

    $this->assertTrue($plugin->detect($source), 'Source detected');
    $data = $plugin->load();
    $this->assertEquals('long', $data['id']);

    // Next, test that the ID is loaded first.
    $source = [
      'core.date_format.long',
      'field.field.node.image.yml'
    ];
    $options = [
      'source' => $source,
      'base' => DRUPAL_ROOT . '/' . drupal_get_path('module', 'test_config_actions'),
    ];

    /** @var \Drupal\config_actions\ConfigActionsSourceInterface $plugin */
    $plugin = $this->sourceManager->createInstance('list', $options);

    $this->assertTrue($plugin->detect($source), 'Source detected');
    $data = $plugin->load();
    $this->assertEquals('long', $data['id']);

    // Finally, test that the File is loaded if the ID doesn't exist.
    $source = [
      'core.date_format.MYFORMAT',
      'field.field.node.image.yml'
    ];
    $options = [
      'source' => $source,
      'base' => DRUPAL_ROOT . '/' . drupal_get_path('module', 'test_config_actions'),
    ];

    /** @var \Drupal\config_actions\ConfigActionsSourceInterface $plugin */
    $plugin = $this->sourceManager->createInstance('list', $options);

    $this->assertTrue($plugin->detect($source), 'Source detected');
    $data = $plugin->load();
    $this->assertEquals('node.@bundle@.@field_name@', $data['id']);

  }

  /**
   * @covers \Drupal\config_actions\Plugin\ConfigActionsSource\ConfigActionsId
   */
  public function testTemplate() {
    $source = 'field.field.node.image';
    $options = [
      'source' => $source,
    ];
    /** @var \Drupal\config_actions\ConfigActionsSourceInterface $plugin */
    $plugin = $this->sourceManager->createInstance('template', $options);

    $this->assertFalse($plugin->detect($source), 'No autodetection');
    $data = $plugin->load();
    $this->assertEquals('@field_name@', $data['field_name']);

    $this->assertFalse($plugin->save([]), 'Should not save');
  }


}
