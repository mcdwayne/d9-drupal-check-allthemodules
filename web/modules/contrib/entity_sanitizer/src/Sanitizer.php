<?php

namespace Drupal\entity_sanitizer;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

class Sanitizer {

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity Type Bundle Info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Entity Field Manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructor for Sanitizer.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   Entity Type Bundle Info service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity Field Manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  public function getAllEntityFieldDefinitions() {
    $entities = [];

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      $entity_id = $entity_type->id();

      // Only check for fieldable entities as
      // the others have no fields to sanitize.
      if (!$entity_type->entityClassImplements(FieldableEntityInterface::class)) {
        continue;
      }

      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_id);

      foreach ($bundles as $bundle_id => $bundle_metadata) {
        $storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_id, $bundle_id);

        if (!isset($entities[$entity_id])) {
          $entities[$entity_id] = [];
        }

        $entities[$entity_id][$bundle_id] = $storage_definitions;
      }
    }

    return $entities;
  }

  /**
   * Generates a safe and unambiguous field table name.
   *
   * The method accounts for a maximum table name length of 64 characters, and
   * takes care of disambiguation.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field storage definition.
   * @param bool $revision
   *   TRUE for revision table, FALSE otherwise.
   *
   * @return string
   *   The final table name.
   *
   * @see \Drupal\Core\Entity\Sql\DefaultTableMapping::generateFieldTableName()
   */
  public function generateFieldTableName(FieldStorageDefinitionInterface $storage_definition, $revision) {
    $separator = $revision ? '_revision__' : '__';
    $table_name = $storage_definition->getTargetEntityTypeId() . $separator . $storage_definition->getName();
    // Limit the string to 48 characters, keeping a 16 characters margin for db
    // prefixes.
    if (strlen($table_name) > 48) {
      // Use a shorter separator, a truncated entity_type, and a hash of the
      // field UUID.
      $separator = $revision ? '_r__' : '__';
      // Truncate to the same length for the current and revision tables.
      $entity_type = substr($storage_definition->getTargetEntityTypeId(), 0, 34);
      $field_hash = substr(hash('sha256', $storage_definition->getUniqueStorageIdentifier()), 0, 10);
      $table_name = $entity_type . $separator . $field_hash;
    }
    return $table_name;
  }

  /**
   * Creates an UPDATE query that sanitizes the given table.
   *
   * The value that is used to replace the current value in the table depends
   * on the field type.
   *
   * TODO: The definition shouldn't be passed around.
   *
   * @param string $table_name
   *   The name of the table that will be updated.
   * @param array $order
   *   This array contains all the information required to create the query.
   *   $order = [
   *     'field_name'        => (string) The system name of the field.
   *     'field_type'        => (string) The field type used to figure out which
   *                            fields need to be changed and what value needs
   *                            to be used as replacement.
   *     'bundles'           => (array) The bundles that will be affected.
   *     'is_revision_table' => (boolean) Whether we're updating a revision
   *                            table or the field base table.
   *     'definition'        => (\Drupal\Core\Field\FieldStorageDefinitionInterface)
   *                            The full field definition that we can use.
   *   ]
   *
   * @return NULL|\Drupal\Core\Database\Query\Update
   *   The generated update query.
   */
  public function generateSanitizeQuery($table_name, array $order) {
    // TODO: Inject this into our service.
    $db = \Drupal::database();

    $field_name = $order['field_name'];
    $field_type = $order['field_type'];

    // TODO: Create $this->>getFieldPlugin() and $field_plugin->getOptions().
    // This setting is required by the geolocation plugin.
    $options = ['allow_delimiter_in_query' => TRUE];
    $query = $db->update($table_name, $options);

    $expressions = $this->getValuesForField($table_name, $field_name, $field_type, $order['definition']);

    if (empty($expressions)) {
      return NULL;
    }

    // Our values contain SQL functions, so we add them as expressions.
    foreach ($expressions as $field_name => $expression) {
      $query->expression($field_name, $expression);
    }

    // Limit the update to our bundles.
//    $query->where('bundle', $order['bundles']);

    return $query;
  }

  /**
   * Specifies values for the database fields of a specific Drupal field type.
   *
   * The values that will be returned
   *
   * @parem $table_name
   *   The table name in which the values will be changed.
   * @param $field_name
   *   The name of the field for which to generate values.
   * @param $field_type
   *   The field type for which to generate values.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
   *   The field definition which we can use to get columns.
   *
   *
   * @return array
   *   An array whose key/value pairs correspond to the parameters of the
   *   Update::expression() function.
   *
   * @throws \Exception
   *   If a passed $field_type has no implementation then we throw an exception.
   */
  protected function getValuesForField($table_name, $field_name, $field_type, $definition) {
    // TODO: Use dependency injection for this.
    /** @var \Drupal\entity_sanitizer\FieldSanitizerManager $sanitizer_manager */
    $sanitizer_manager = \Drupal::service('plugin.manager.field_sanitizer');

    $columns = $definition->getColumns();

    switch($field_type) {
      // For text fields we sanitize the textual value.
      case 'text_with_summary':
      case 'string':
      case 'string_long':
      case 'text_long':
      case 'email':
      case 'link':
      case 'image':
      case 'file':
      case 'telephone':
      case 'address':
      case 'geolocation':
        try {
          return $sanitizer_manager
            ->createInstance($field_type)
            ->getFieldValues($table_name, $field_name, $columns);
        }
        catch(PluginNotFoundException $e) {
          throw new UnsupportedFieldTypeException($field_type, $field_name);
        }
      // These types don't contain sensitive information so we skip them.
      // This because they're either values that are part of code or references
      // to other (sanitized) entities.
      // TODO: Create an empty sanitizer for these fields.
      case 'block_field':
      case 'boolean':
      case 'dropdown':
      case 'datetime':
      case 'comment':
      case 'list_string':
      case 'list_integer':
      case 'entity_access_field':
      case 'entity_reference':
      case 'entity_reference_revisions':
      case 'dynamic_entity_reference':
      case 'video_embed_field':
      case 'weight':
        $fields = [];
        break;
      default:
        throw new UnsupportedFieldTypeException($field_type, $field_name);
    }

    return $fields;
  }
}
