<?php

namespace Drupal\Tests\config_ignore_keys\Kernel;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Class ConfigIgnoreTest.
 *
 * @group config_ignore_keys
 */
class ConfigIgnoreTest extends KernelTestBase {

  /**
   * The modules that will be enabled during testing.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'field',
    'text',
    'user',
    'node',
    'language',
    'configuration_ignore_test',
    'config_ignore_keys',
  ];

  /**
   * Sync storage.
   *
   * @var \Drupal\Core\Config\FileStorage
   */
  protected $sync;

  /**
   * The configuration importer used to test the import.
   *
   * @var \Drupal\Core\Config\ConfigImporter
   */
  protected $configImporter;

  /**
   * The machine name for the test node type.
   */
  const NODE_TYPE_NAME = 'test_node_type';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig(['field', 'node']);

    // Load sync configuration.
    $this->sync = $this->container->get('config.storage.sync');

    $this->copyConfig($this->container->get('config.storage'), $this->sync);

    // Set up the ConfigImporter object for testing.
    $storage_comparer = new StorageComparer(
      $this->container->get('config.storage.sync'),
      $this->container->get('config.storage'),
      $this->container->get('config.manager')
    );
    $this->configImporter = new ConfigImporter(
      $storage_comparer->createChangelist(),
      $this->container->get('event_dispatcher'),
      $this->container->get('config.manager'),
      $this->container->get('lock'),
      $this->container->get('config.typed'),
      $this->container->get('module_handler'),
      $this->container->get('module_installer'),
      $this->container->get('theme_handler'),
      $this->container->get('string_translation')
    );

    // Import.
    $this->configImporter->reset()->import();

    // Create a new content type.
    $content_type = NodeType::create([
      'type' => static::NODE_TYPE_NAME,
      'name' => 'Test node type',
    ]);
    $content_type->save();
    // Add a body field.
    node_add_body_field($content_type);

    // Copy the current config over the sync config set.
    $active = $this->container->get('config.storage');
    $this->copyConfig($active, $this->sync);
    $this->configImporter->reset()->import();
  }

  /**
   * Verifies that items are ignored correctly.
   */
  public function testConfigIgnored() {
    $content_type = NodeType::load(static::NODE_TYPE_NAME);
    // Change the content type name.
    $content_type->set('name', 'New node type name');
    $content_type->save();

    $this->configImporter->reset();
    $updates = $this->configImporter->getUnprocessedConfiguration('update');
    self::assertEquals(1, count($updates), 'There is 1 configuration item to update.');

    // Import the configuration containing the old content type node.
    $this->configImporter->reset()->import();

    // Load the content type after import.
    $content_type = NodeType::load(static::NODE_TYPE_NAME);
    // Check that the content type name change was not imported.
    self::assertNotEquals($content_type->get('name'), 'Test node type');
  }

  /**
   * Verifies items import correctly.
   */
  public function testConfigNotIgnored() {
    $content_type = NodeType::load(static::NODE_TYPE_NAME);
    // Change the body field name. This should not be ignored by the import.
    $field = FieldConfig::loadByName('node', $content_type->id(), 'body');
    $field->set('label', 'New label');
    $field->save();

    $this->configImporter->reset();
    $updates = $this->configImporter->getUnprocessedConfiguration('update');
    self::assertEquals(1, count($updates), 'There is 1 configuration item to update.');

    // Import the configuration containing the old content type node.
    $this->configImporter->reset()->import();

    // Check that the body field label was changed, as it should not be ignored.
    $field = FieldConfig::loadByName('node', $content_type->id(), 'body');
    self::assertEquals($field->get('label'), 'Body');
  }

}
