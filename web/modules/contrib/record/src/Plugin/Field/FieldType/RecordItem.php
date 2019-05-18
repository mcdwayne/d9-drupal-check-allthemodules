<?php

namespace Drupal\record\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Plugin implementation of the 'record_item' field type.
 *
 * @FieldType(
 *   id = "record_item",
 *   label = @Translation("Record properties"),
 *   module = "record",
 *   description = @Translation("Multi-value field, for record data that doesn't require full drupal field API support"),
 *   default_widget = "record_item_widget",
 *   default_formatter = "record_item_formatter"
 * )
 */
class RecordItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $field_name = $field_definition->getName();

    if (!self::getExistingTable($field_name)) {
      // When the field doesn't exist, say it is being created, we need get in-code fields.
      return self::getCodeSchema($field_name);
    }

    // By default, don't surprise Drupal with properties that don't match the database.
    return self::getDatabaseSchema($field_name);

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $field_name = $field_definition->getName();
    $schema = self::getExtendedSchema($field_name);

    foreach ($schema['columns'] as $key => $column) {
      $label = isset($column['record_properties']['label']) ? $column['record_properties']['label'] : $key;
      switch ($column['type']) {
        case 'text':
        case 'varchar':
          $properties[$key] = DataDefinition::create('string')
            ->setLabel(t($label));
          break;

        case 'int':
          $properties[$key] = DataDefinition::create('integer')
            ->setLabel(t($label));
          break;

        default:
          $properties[$key] = DataDefinition::create('string')
            ->setLabel('Missing ' . $column['type'] . "for $key");
          break;
      }
    }

    return $properties;
  }

  /**
   * The default schema every instance of this field will have.
   *
   * @return string|false
   *   Name of the existing field table.
   */
  public static function getExistingTable($field_name) {
    $connection = \Drupal::database();
    if ($existing_table = $connection->query('SHOW TABLES LIKE :table', [':table' => "record__{$field_name}"])->fetch()) {
      return $existing_table;
    }

    return FALSE;
  }

  /**
   * The default schema every instance of this field will have.
   *
   * @return array
   *   An array that conforms to the schema definition for a field item.
   */
  private static function getDefaultSchema() {
    return [
      'columns' => [
        'archived_fields' => [
          'type' => 'varchar',
          'length' => 256,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * Gets extended schema definitions for the record data field.
   *
   * @param string $field_name
   *   A record item field name.
   *
   * @return array
   *   An array that conforms to the schema definition for a field item.
   */
  public static function getCodeSchema(string $field_name) {
    $schema = self::getDefaultSchema();
    if ($field_name == 'data') {
      // This is the default persistent unattached field, never customized.
      return $schema;
    }

    $moduleHandler = \Drupal::moduleHandler();
    $schema = array_merge_recursive($schema, $moduleHandler->invokeAll('record_extended_schema', [$field_name]));

    return $schema;
  }

  /**
   * Gets the schema definition from the current database table.
   *
   * @param string $field_name
   *   A record item field name.
   *
   * @return array
   *   An array that conforms to the schema definition for a field item.
   */
  public static function getDatabaseSchema(string $field_name) {
    $schema = self::getDefaultSchema();
    if ($field_name == 'data') {
      // This is the default persistent unattached field, never customized.
      return $schema;
    }

    // Inspect and match current fields.
    // Since we allow the property schema to be changed regularly, we need a
    // stable way to tell drupal about our field tables properties that
    // actually exist and not rely on the code. This essentially means that
    // when the cache is cleared, and the field properties are rebuilt, we
    // need to know the difference between the desired field table and the actual field table.
    //
    // Table name should always be "{bundle_of}__{field_name}" and the field name is
    // always the bundle name.
    $connection = \Drupal::database();
    $existing_table = self::getExistingTable($field_name);
    if ($existing_table && $result = $connection->query("SHOW COLUMNS IN record__{$field_name}")) {
      while ($column = $result->fetchAssoc()) {
        if ($property_name = self::getInferredPropertyNameFromDbColumn($field_name, $column['Field'])) {
          $schema['columns'][$property_name] = self::getStubField($property_name, $column);
        }
      }
    }

    // Our database definitions are rudimentary, so use code-defined definitions if we have them.
    $in_code = self::getCodeSchema($field_name);
    foreach ($schema['columns'] as $column => $properties) {
      if (isset($in_code['columns'][$column])) {
        $schema['columns'][$column] = $in_code['columns'][$column];
      }
    }

    return $schema;
  }

  /**
   * Gets extended schema definitions for the record data field.
   *
   * @param string $field_name
   *   A record item field name.
   *
   * @return array
   *   An array that conforms to the schema definition for a field item.
   */
  public static function getExtendedSchema(string $field_name) {
    $in_code_schema = ['columns' => []];
    $in_db_schema = ['columns' => []];
    $connection = \Drupal::database();

    // Define the desired schema state.

    $in_code_schema['columns']['archived_fields'] = [
      'type' => 'varchar',
      'length' => 256,
      'not null' => FALSE,
    ];
    if ($field_name == 'data') {
      // This is the default persistent unattached field, never customized.
      return $in_code_schema;
    }

    $moduleHandler = \Drupal::moduleHandler();
    $in_code_schema = array_merge_recursive($in_code_schema, $moduleHandler->invokeAll('record_extended_schema', [$field_name]));

    $existing_table = $connection->query('SHOW TABLES LIKE :table', [':table' => "record__{$field_name}"]);
    if (!$existing_table->fetch()) {
      // Field doesn't exist in database yet, so we can use code version.
      return $in_code_schema;
    }

    // Inspect and match current fields.
    // Since we allow the property schema to be changed regularly, we need a
    // stable way to tell drupal about our field tables properties that
    // actually exist and not rely on the code. This essentially means that
    // when the cache is cleared, and the field properties are rebuilt, we
    // need to know the difference between the desired field table and the actual field table.
    //
    // Table name should always be "{bundle_of}__{field_name}" and the field name is
    // always the bundle name.
    if ($existing_table && $result = $connection->query("SHOW COLUMNS IN record__{$field_name}")) {
      while ($column = $result->fetchAssoc()) {
        if ($property_name = self::getInferredPropertyNameFromDbColumn($field_name, $column['Field'])) {
          $in_db_schema['columns'][$property_name] = self::getStubField($property_name, $column);
        }
      }
    }

    if (count($in_code_schema['columns']) != count($in_db_schema['columns'])) {
      // There is a schema difference waiting to be processed.
    }

    $schema = ['columns' => []];
    $combined_keys = array_unique(array_merge(array_keys($in_code_schema['columns']), array_keys($in_db_schema['columns'])));
    foreach ($combined_keys as $column) {
      if (isset($in_code_schema['columns'][$column])) {
        // Prefer code-defined fields if we have them.
        $schema['columns'][$column] = $in_code_schema['columns'][$column];
      }
      else {
        // Only in the database.
        $schema['columns'][$column] = $in_db_schema['columns'][$column];
      }
    }

    return $schema;
  }

  /**
   * Taking a raw database column, determine the property name.
   *
   * @param string $field_name
   *   The bundle/field name (which are the same).
   * @param string $column_name
   *   The raw database column name.
   *
   * @return bool|string
   *   The expected property value.
   */
  public static function getInferredPropertyNameFromDbColumn($field_name, $column_name) {
    // Ignore the columns that aren't custom to the field. Could be more dynamic?
    // Since the custom properties are prefixed {field_name}_property, and we
    // could identify them that way, I am doing this a slightly cludgy way to
    // see how things fly.
    $reserved = [
      'bundle',
      'deleted',
      'entity_id',
      'revision_id',
      'langcode',
      'delta',
    ];

    if (in_array($column_name, $reserved)) {
      return FALSE;
    }

    if (strpos($column_name, $field_name) === 0) {
      $property_name = substr($column_name, strlen($field_name) + 1);
      return $property_name;
    }

    return FALSE;
  }

  /**
   * Turn a database column information.
   *
   * @param string $property_name
   *   Drupal field property name.
   * @param array $column
   *   Database column info as returned by "SHOW COLUMNS" statement.
   *
   * @return array
   *   A propertyDefinition as required to be returned by propertyDefinitions().
   *
   * @throws \Exception
   */
  public static function getStubField(string $property_name, array $column) {

    if (strpos($column['Type'], 'varchar') === 0 || strpos(' ' . $column['Type'], 'text')) {
      return [
        'type' => 'text',
        'size' => 'big',
        'not null' => FALSE,
        'record_properties' => [
          'label' => $property_name,
        ],
      ];
    }

    if (strpos(' ' . $column['Type'], 'int')) {
      return [
        'type' => 'int',
        'size' => 'tiny',
        'unsigned' => TRUE,
        'not null' => FALSE,
        'record_properties' => [
          'label' => $property_name,
        ],
      ];
    }

    if (strpos($column['Type'], 'float') === 0) {
      return [
        'type' => 'float',
        'properties' => [
          'title' => $property_name,
        ],
      ];
    }

    if (strpos($column['Type'], 'blob') === 0) {
      return [
        'type' => 'blob',
        'serialize' => TRUE,
        'properties' => [
          'title' => $property_name,
        ],
      ];
    }

    throw new \Exception('This database field type ' . $column['Type'] . ' is not handled yet.');
  }

}
