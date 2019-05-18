<?php
/**
 * @file
 * Contains Drupal\schema\Plugin\Schema\Entity.
 */

namespace Drupal\schema\Plugin\Schema;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\schema\SchemaProviderInterface;
use ReflectionClass;

/**
 * Provides schema information related to entities and fields.
 *
 * @SchemaProvider(id = "entity")
 */
class Entity extends PluginBase implements SchemaProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = \Drupal::entityManager();
  }

  /**
   * {@inheritdoc}
   */
  public function get($rebuild = FALSE) {
    $complete_schema = array();
    $entity_types = $this->entityManager->getDefinitions();
    foreach ($entity_types as $entity_type) {
      $complete_schema += $this->getTypeSchema($entity_type, $rebuild);
    }
    return $complete_schema;
  }

  protected function getTypeSchema(EntityTypeInterface $entity_type, $rebuild) {
    $storage = $this->entityManager->getStorage($entity_type->id());

    // We need to build the entity schema from entity base tables as well as
    // shared and dedicated field tables. Too bad core doesn't this for us. :-/
    $entity_schema = array();

    if ($entity_type instanceof ContentEntityTypeInterface && $storage instanceof SqlContentEntityStorage) {
      $class = $entity_type->getHandlerClass('storage_schema') ?: 'Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema';
      $storage_schema = new $class($this->entityManager, $entity_type, $storage, \Drupal::database());
      $table_mapping = $storage->getTableMapping();

      // Use reflection to access protected methods.
      $reflection = new ReflectionClass($class);
      $entity_schema_method = $reflection->getMethod('getEntitySchema');
      $entity_schema_method->setAccessible(TRUE);
      $entity_schema += $entity_schema_method->invoke($storage_schema, $entity_type, $rebuild);

      // Add schema for shared field tables
      $field_types = $this->entityManager->getFieldStorageDefinitions($entity_type->id());
      foreach ($field_types as $field_storage) {
        if ($table_mapping->requiresDedicatedTableStorage($field_storage)) {
          $entity_schema_method = $reflection->getMethod('getDedicatedTableSchema');
          $entity_schema_method->setAccessible(TRUE);
          $entity_schema += $entity_schema_method->invoke($storage_schema, $field_storage);
        }
        elseif ($table_mapping->allowsSharedTableStorage($field_storage)) {
          $field_name = $field_storage->getName();
          foreach (array_diff($table_mapping->getTableNames(), $table_mapping->getDedicatedTableNames()) as $table_name) {
            if (in_array($field_name, $table_mapping->getFieldNames($table_name))) {
              $column_names = $table_mapping->getColumnNames($field_name);

              $entity_schema_method = $reflection->getMethod('getSharedTableFieldSchema');
              $entity_schema_method->setAccessible(TRUE);
              $entity_schema[$table_name] += $entity_schema_method->invoke($storage_schema, $field_storage, $table_name, $column_names);
            }
          }
        }
        else {
          // Field uses custom storage which we cannot determine automatically.
          // @todo Is this correct?
        }
      }
    }

    // Add module information, if it doesn't exist.
    foreach ($entity_schema as $name => &$table) {
      if (!isset($table['module'])) {
        $table['module'] = $entity_type->getProvider();
      }
    }

    return $entity_schema;
  }
}
