<?php

namespace Drupal\Tests\config_actions\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Serialization\Yaml;
use Drupal\config_actions\ConfigActionsTransform;

/**
 * test the ConfigActions plugins
 *
 * @group config_actions
 */
class ConfigActionsPluginTest extends KernelTestBase {

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
   * Helper function to delete a specific configuration item
   * @param string $id
   */
  protected function deleteConfig($id) {
    return $this->container->get('config.factory')->getEditable($id)->delete();
  }

  /**
   * Test string replacement.
   */
  public function testReplace() {
    // Config data taken from core.date_format.long.yml
    // Additional keys added to test the string and key replacement system
    $source = [
      'langcode' => 'en',
      'status' => true,
      'dependencies' => [],
      'id' => '@format@',
      'label' => 'Default long date',
      'locked' => false,
      'pattern' => 'l, F j, Y - H:i',
      '@key@' => 'date',
    ];
    // $vars get replaced everywhere (options AND source data values and keys)
    $replace = [
      // Test replacement in 'value' option.
      '@test@' => 'new',
      // Test replacement in loaded data value (id).
      '@format@' => 'long',
      // Test replacement in loaded data key.
      '@key@' => 'date',
      //
      'date' => [
        'with' => 'datetime',
        'type' => 'value',
        // Only change loaded data values, so does not replace in 'value' option
        'in' => ['load'],
      ],
      'label' => [
        'with' => 'mylabel',
        // Only change loaded key values, so does not replace in 'path' option
        'type' => 'key',
      ],
    ];
    $value = 'My @test@ date';
    $action = [
      'plugin' => 'change',
      'source' => $source,
      // Original 'label' key was changed to 'mylabel'
      // But path is not replaced with 'mymylabel' because only keys were replaced.
      'path' => ['mylabel'],
      'value' => $value,
      'replace' => $replace,
      'replace_in' => ['value', 'load', 'path'],
    ];

    $new_config = $this->configActions->processAction($action);

    $source['mylabel'] = 'My new date';
    $source['id'] = 'long';
    unset($source['label']);
    $source['date'] = 'datetime';
    unset($source['@key@']);

    self::assertEquals($source, $new_config);
  }

  /**
   * Test using @property@ replacement in options
   */
  public function testOptionReplace() {
    $action = [
      'plugin' => 'change',
      'source' => '@id@',
      'path' => ['label'],
      'actions' => [
        'core.date_format.short' => [
          'value' => 'Test short date',
        ],
        'core.date_format.long' => [
          'value' => 'Test long date',
        ],
      ],
    ];

    $config = $this->configActions->processAction($action);
    $short_config = $this->getConfig('core.date_format.short');
    $long_config = $this->getConfig('core.date_format.long');
    self::assertEquals('Test short date', $short_config['label']);
    self::assertEquals('Test long date', $long_config['label']);
  }

  /**
   * Test "source" option that is used in many plugins.
   */
  public function testSource() {
    $source = [
      'mykey' => 'myvalue',
      'label' => 'This is a @test@',
    ];
    $dest = 'core.date_format.long';
    $replace = [
      'label' => [
        'with' => 'mylabel',
        // We only want to replace the 'value' option, not the 'path'.
        'in' => ['value'],
      ],
      '@test@' => 'new',
    ];
    $value = 'My @test@ label';
    $action = [
      'plugin' => 'change',
      'source' => $source,
      'dest' => $dest,
      'path' => ['label'],
      'value' => $value,
      'replace' => $replace,
    ];

    $orig_config = $this->getConfig($dest);
    $tree = $this->configActions->processAction($action);

    $new_config = $this->getConfig($dest);
    $source['label'] = 'My new mylabel';

    // First, test the raw return value
    self::assertEquals($source, $tree);

    // Now test what was actually stored in the config because of the schema
    // where you can't just add the 'newkey'
    $orig_config['label'] = 'My new mylabel';
    self::assertEquals($orig_config, $new_config);
    self::assertArrayNotHasKey('mykey', $new_config);
  }

  /**
   * @covers \Drupal\config_actions\Plugin\ConfigActions\ConfigActionsDelete
   */
  public function testDelete() {
    $source = 'core.date_format.long';
    $action = [
      'plugin' => 'delete',
      'source' => $source,
      'path' => ['label'],
    ];

    $orig_config = $this->getConfig($source);
    $this->configActions->processAction($action);

    $new_config = $this->getConfig($source);
    $orig_config['label'] = '';

    self::assertEquals($orig_config, $new_config);

    // Test pruning the data
    $source = 'core.date_format.long';
    $action = [
      'plugin' => 'delete',
      'source' => $source,
      'path' => ['label'],
      'prune' => TRUE,
    ];

    $orig_config = $this->getConfig($source);
    $new_config = $this->configActions->processAction($action);
    // Cannot use getConfig to test because Drupal won't actually delete the key
    unset($orig_config['label']);

    self::assertEquals($orig_config, $new_config);

    // Test deleting entire config entity
    $source = 'core.date_format.long';
    $action = [
      'plugin' => 'delete',
      'source' => $source,
    ];

    $this->configActions->processAction($action);
    $new_config = $this->getConfig($source);
    self::assertEmpty($new_config);
  }

  /**
   * @covers \Drupal\config_actions\Plugin\ConfigActions\ConfigActionsChange
   */
  public function testChange() {
    $source = 'core.date_format.long';
    $value = 'My new label';
    $action = [
      'plugin' => 'change',
      'source' => $source,
      'path' => ['label'],
      'value' => $value,
    ];

    $orig_config = $this->getConfig($source);
    $this->configActions->processAction($action);

    $new_config = $this->getConfig($source);
    $orig_config['label'] = $value;

    self::assertEquals($orig_config, $new_config);

    $value = 'Another new label';
    $action = [
      'plugin' => 'change',
      'source' => $source,
      'value' => [
        'label' => $value,
        ]
    ];

    $orig_config = $this->getConfig($source);
    $this->configActions->processAction($action);

    $new_config = $this->getConfig($source);
    $orig_config['label'] = $value;

    self::assertEquals($orig_config, $new_config);

    $new_value = 'New Value';
    $action = [
      'plugin' => 'change',
      'source' => $source,
      'path' => ['label'],
      'value' => $new_value,
      'current_value' => $value,
    ];

    $orig_config = $this->getConfig($source);
    $this->configActions->processAction($action);

    $new_config = $this->getConfig($source);
    $orig_config['label'] = $new_value;

    self::assertEquals($orig_config, $new_config);

    $action = [
      'plugin' => 'change',
      'source' => $source,
      'path' => ['label'],
      'value' => $value,
      'current_value' => 'NONE',
    ];
    $this->setExpectedException(\Exception::class, 'Failed to validate path value for config action.');
    $this->configActions->processAction($action);
  }

  /**
   * @covers \Drupal\config_actions\Plugin\ConfigActions\ConfigActionsAdd
   */
  public function testAdd() {
    // Find a config file that has an array we can add to without violating schema.
    $source = 'system.action.node_delete_action';
    // Create a new key and value in config.
    $value = [
      'abc' => 123,
      'def' => 'test',
    ];
    $action = [
      'plugin' => 'add',
      'source' => $source,
      'path' => ['newkey'],
      'value' => $value,
    ];

    $orig_config = $this->getConfig($source);
    $tree = $this->configActions->processAction($action);

    $orig_config['newkey'] = $value;
    // First check raw data transformation contains new key.
    self::assertEquals($orig_config, $tree);
    // Now check actual stored config since you can't just add new key values to schema.
    $new_config = $this->getConfig($source);
    self::assertArrayNotHasKey('newkey', $new_config);

    // "Append" additional data to existing key.
    $value = 'mydata';
    $action = [
      'plugin' => 'add',
      'source' => $source,
      'path' => ['configuration'],
      'value' => $value,
    ];

    $orig_config = $this->getConfig($source);
    $this->configActions->processAction($action);

    $new_config = $this->getConfig($source);
    $orig_config['configuration'] = [$value];
    self::assertEquals($orig_config, $new_config);

    // Now add another additional value to existing key.
    $value2 = 'another';
    $action = [
      'plugin' => 'add',
      'source' => $source,
      'path' => ['configuration'],
      'value' => $value2,
    ];

    $orig_config = $this->getConfig($source);
    $this->configActions->processAction($action);

    $new_config = $this->getConfig($source);
    $orig_config['configuration'] = [$value, $value2];
    self::assertEquals($orig_config, $new_config);

    // Test "change" vs "add".
    $action = [
      'plugin' => 'change',
      'source' => $source,
      'path' => ['configuration'],
      'value' => [$value],
    ];

    $orig_config = $this->getConfig($source);
    $this->configActions->processAction($action);

    $new_config = $this->getConfig($source);
    $orig_config['configuration'] = [$value];
    self::assertEquals($orig_config, $new_config);
  }

  /**
   * Test nested actions.
   */
  public function testNested() {
    $source = 'core.date_format.@format@';
    $value = 'My new label';
    $action = [
      // Test global options passed to actions.
      'source' => $source,
      'path' => ['label'],
      'replace_in' => ['source'],
      'actions' => [
        'long-action' => [
          'replace' => ['@format@' => 'long'],
          'actions' => [
            'change-action' => [
              'plugin' => 'change',
              'value' => $value,
            ],
            'change-status' => [
              'plugin' => 'change',
              // Test overriding path option for specific action.
              'path' => ['locked'],
              'value' => true,
            ],
          ],
        ],
        'short-action' => [
          'plugin' => 'change',
          'value' => $value,
          // Test a different format variable
          'replace' => ['@format@' => 'short'],
        ],
      ],
    ];

    $orig_config_long = $this->getConfig('core.date_format.long');
    $orig_config_short = $this->getConfig('core.date_format.short');
    $this->configActions->processAction($action);

    $new_config_long = $this->getConfig('core.date_format.long');
    $new_config_short = $this->getConfig('core.date_format.short');
    $orig_config_long['label'] = $value;
    $orig_config_long['locked'] = true;
    $orig_config_short['label'] = $value;

    self::assertEquals($orig_config_long, $new_config_long);
    self::assertEquals($orig_config_short, $new_config_short);
  }

  /**
   * @covers \Drupal\config_actions\Plugin\ConfigActions\ConfigActionsChange
   */
  public function testTemplate() {
    // Test basic template function using change plugin.
    $config_id = 'field.storage.node.myimage';
    $source_file = dirname(__FILE__) . '/field.storage.node.image.yml';
    $dest = 'field.storage.node.@field_name@';
    $replace = [
      '@field_name@' => 'myimage',
      '@cardinality@' => 2,
    ];
    $action = [
      'source' => $source_file,
      'dest' => $dest,
      'replace' => $replace,
    ];

    $orig_config = Yaml::decode(file_get_contents($source_file));
    $orig_config = ConfigActionsTransform::replace($orig_config, $replace);
    $this->configActions->processAction($action);

    $new_config = $this->getConfig($config_id);

    self::assertEquals($orig_config, $new_config);
    self::assertTrue(is_int($new_config['cardinality']), 'Cardinality test should set config as integer value');

    // Clean up for next test.
    $this->deleteConfig($config_id);

    // Test replace_in to prevent string replacement
    $dest = 'field.storage.node.field_name';
    $replace = [
      'field_name' => 'myimage',
      '@cardinality@' => 1,
    ];
    $action = [
      'source' => $source_file,
      'dest' => $dest,
      'replace' => $replace,
      'replace_in' => [],
    ];

    $orig_config = Yaml::decode(file_get_contents($source_file));
    $tree = $this->configActions->processAction($action);
    self::assertEquals($orig_config, $tree);

    // Check saved config
    $new_config = $this->getConfig($dest);
    self::assertEquals($orig_config, $new_config);

    // Ensure config didn't get created with new name.
    $new_config = $this->getConfig($config_id);
    self::assertEmpty($new_config);

    // Clean up for next test.
    $this->deleteConfig($config_id);

    // Test using an array of sources to override existing config
    // First time it should create new config
    $dest = $config_id;
    $replace = [
      '@field_name@' => 'myimage',
    ];
    $action = [
      'source' => [
        '@dest@',
        $source_file,
      ],
      'dest' => $dest,
      'replace' => $replace,
      'value' => [
        'cardinality' => 2,
        'translatable' => true,
      ]
    ];

    $orig_config = Yaml::decode(file_get_contents($source_file));
    $orig_config = ConfigActionsTransform::replace($orig_config, $replace);
    $orig_config['cardinality'] = 2;
    $orig_config['translatable'] = true;
    $this->configActions->processAction($action);

    $new_config = $this->getConfig($config_id);

    self::assertEquals($orig_config, $new_config);

    // Second time it should use existing config
    // Changing 'translatable', but 'cardinality' should still be 2,
    // and NOT the 1 that is in the original template/default
    // Clear cache to ensure previous data isn't still being used.
    $this->configActions->clearSourceCache();
    $dest = $config_id;
    $replace = [
      '@field_name@' => 'myimage',
    ];
    $action = [
      'source' => [
        '@dest@',
        $source_file,
      ],
      'dest' => $dest,
      'replace' => $replace,
      'value' => [
        'translatable' => false,
      ]
    ];

    $orig_config = Yaml::decode(file_get_contents($source_file));
    $orig_config = ConfigActionsTransform::replace($orig_config, $replace);
    $orig_config['cardinality'] = 2;
    $orig_config['translatable'] = false;
    $this->configActions->processAction($action);

    $new_config = $this->getConfig($config_id);

    self::assertEquals($orig_config, $new_config);
  }

  /**
   * @covers \Drupal\config_actions\Plugin\ConfigActions\ConfigActionsInclude
   */
  public function testInclude() {
    // First, just call the field_storage action to test a simple include.
    $replace = [
      '@field_name@' => 'myimage',
      '@cardinality@' => 1,
    ];
    $action = [
      'plugin' => 'include',
      'module' => 'test_config_actions',
      'action' => 'field_storage',
      'replace' => $replace,
    ];

    $path = drupal_get_path('module', 'test_config_actions') . '/config/templates';
    $source = $path . '/field.storage.node.image.yml';
    $orig_config = Yaml::decode(file_get_contents($source));
    $orig_config = ConfigActionsTransform::replace($orig_config, $replace);
    $this->configActions->processAction($action);

    $new_config = $this->getConfig('field.storage.node.myimage');

    self::assertEquals($orig_config, $new_config);
    self::assertTrue(is_int($new_config['cardinality']), 'Cardinality test should set config as integer value');

    // Next, call the field_instance action which has two sub-actions that have
    // their own bundles defined.
    $replace = [
      '@field_name@' => 'myimage',
      // This bundle is ignored because sub-actions have their own bundle replace.
      '@bundle@' => 'mypage',
    ];
    $replace_article = [
      '@field_name@' => 'myimage',
      '@bundle@' => 'article',
    ];
    $replace_page = [
      '@field_name@' => 'myimage',
      '@bundle@' => 'page',
    ];
    $action = [
      'plugin' => 'include',
      'module' => 'test_config_actions',
      'action' => 'field_instance',
      'replace' => $replace,
    ];

    $path = drupal_get_path('module', 'test_config_actions') . '/config/templates';
    $source = $path . '/field.field.node.image.yml';
    $orig_config = Yaml::decode(file_get_contents($source));
    $orig_config_article = ConfigActionsTransform::replace($orig_config, $replace_article);
    $orig_config_page = ConfigActionsTransform::replace($orig_config, $replace_page);
    $this->configActions->processAction($action);

    $new_config_article = $this->getConfig('field.field.node.article.myimage');
    $new_config_page = $this->getConfig('field.field.node.page.myimage');

    self::assertEquals($orig_config_article, $new_config_article);
    self::assertEquals($orig_config_page, $new_config_page);

    $this->deleteConfig('field.field.node.article.myimage');
    $this->deleteConfig('field.field.node.page.myimage');

    // Next, call one of the sub-actions directly.
    // Now we can override the bundle in the sub-action.
    $action = [
      'plugin' => 'include',
      'module' => 'test_config_actions',
      'action' => 'field_instance:article',
      'replace' => $replace,
    ];

    $orig_config = Yaml::decode(file_get_contents($source));
    $orig_config = ConfigActionsTransform::replace($orig_config, $replace);
    $this->configActions->processAction($action);

    $new_config = $this->getConfig('field.field.node.mypage.myimage');
    $new_config_article = $this->getConfig('field.field.node.article.myimage');
    $new_config_page = $this->getConfig('field.field.node.page.myimage');

    self::assertEquals($orig_config, $new_config);
    self::assertEmpty($new_config_article);
    self::assertEmpty($new_config_page);

    // Next, run actions in a single file.
    $config_id = 'core.date_format.short';
    $action = [
      'plugin' => 'include',
      'module' => 'test_config_actions',
      'file' => $config_id,
    ];
    $this->configActions->processAction($action);

    $new_config = $this->getConfig($config_id);
    self::assertEquals('Test short date', $new_config['label']);

    // Test allowing .yml in the file name.
    $config_id = 'core.date_format.short';
    $label = 'New short date';
    $action = [
      'plugin' => 'include',
      'module' => 'test_config_actions',
      'file' => $config_id . '.yml',
      '@label@' => $label,
    ];
    $this->configActions->processAction($action);

    $new_config = $this->getConfig($config_id);
    self::assertEquals($label, $new_config['label']);
  }

  /**
   * Test the pipeline for saving config
   */
  function testPipeline() {
    $source = 'user.role.myrole';
    $action = [
      // Test global options passed to actions.
      'source' => $source,
      'path' => ['permissions'],
      'actions' => [
        'add-permission-1' => [
          'plugin' => 'add',
          'value' => 'permission1',
        ],
        // Doing a Prune here would fail without a pipeline since the
        // config couldn't be saved without the permissions config key.
        'delete-permissions' => [
          'plugin' => 'delete',
          'prune' => TRUE,
        ],
        'add-permission-2' => [
          'plugin' => 'add',
          'value' => 'permission2',
        ],
      ],
    ];

    $this->configActions->processAction($action);
    $new_config = $this->getConfig($source);
    self::assertEquals(['permission2'], $new_config['permissions']);
  }
}
