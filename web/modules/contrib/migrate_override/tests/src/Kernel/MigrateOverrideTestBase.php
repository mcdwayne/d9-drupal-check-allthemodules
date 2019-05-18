<?php

namespace Drupal\Tests\migrate_override\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Base Class for Migrate Override Kernel Tests.
 *
 * @group migrate_override
 */
abstract class MigrateOverrideTestBase extends MigrateTestBase {

  public static $modules = [
    'node',
    'migrate',
    'field',
    'system',
    'user',
    'migrate_override',
  ];

  /**
   * The override manager service.
   *
   * @var \Drupal\migrate_override\OverrideManagerServiceInterface
   */
  protected $overrideManager;

  /**
   * The editable config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', 'node_access');
    $this->installConfig('migrate_override');
    $this->overrideManager = $this->container->get('migrate_override.override_manager');
    $this->config = $this->container->get('config.factory')->getEditable('migrate_override.migrateoverridesettings');
    $this->migrationPluginManager = $this->container->get('plugin.manager.migration');
  }

  /**
   * Creates a test node type.
   *
   * @param string $type
   *   The type machine name.
   * @param string $name
   *   The type name.
   *
   * @return \Drupal\node\Entity\NodeType
   *   The new node type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createContentType($type = 'test_type', $name = 'Test Content Type') {
    $type = NodeType::create([
      'name' => $name,
      'type' => $type,
    ]);
    $type->save();
    return $type;
  }

  /**
   * Adds a test field to a content type.
   *
   * @param string $field_name
   *   The field machine name.
   * @param string $content_type
   *   The content type.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addTextField($field_name, $content_type = 'test_type') {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'string',
      'settings' => [
        'max_length' => 256,
        'is_ascii' => FALSE,
        'case_sensitive' => FALSE,
      ],
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $content_type,
      'label' => "Field $field_name",
    ])->save();
  }

}
