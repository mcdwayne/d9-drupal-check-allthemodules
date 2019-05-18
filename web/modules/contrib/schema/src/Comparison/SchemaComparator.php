<?php
/**
 * @file
 * Contains Drupal\schema\Comparison\SchemaComparator.
 */

namespace Drupal\schema\Comparison;

use Drupal\schema\Comparison\Result\SchemaComparison;
use Drupal\schema\DatabaseSchemaInspectionInterface;


/**
 * Compares a declared schema array with the complete database schema.
 *
 * @todo Extract table schema comparison into separate class which can be used on it's own, e.g. via schema_compare_table().
 */
class SchemaComparator {

  /**
   * @var DatabaseSchemaInspectionInterface
   */
  protected $inspector;

  /**
   * @var array
   */
  protected $declared_schema;

  /**
   * @var SchemaComparison
   */
  protected $result = NULL;

  /**
   * Constructs a new SchemaComparator instance.
   *
   * @param array $declared_schema
   *   The declared schema.
   * @param DatabaseSchemaInspectionInterface $inspector
   *   The database inspector used to retrieve the actual schema.
   */
  public function __construct(array $declared_schema, DatabaseSchemaInspectionInterface $inspector) {
    $this->declared_schema = $declared_schema;
    $this->inspector = $inspector;
  }

  /**
   * Compare declared schema and the default database schema.
   *
   * @return SchemaComparison
   *   The comparison result.
   */
  public static function compareDefault() {
    return (new self(schema_get_schema(), schema_dbobject()))->execute();
  }

  /**
   * Execute the schema comparison.
   *
   * @return SchemaComparison
   *   The comparison result.
   */
  public function execute() {
    if ($this->result == NULL) {
      $this->result = new SchemaComparison();
      $this->executeCompare();
    }
    return $this->result;
  }

  /**
   * Generates comparison information and stores it in the $result field.
   */
  protected function executeCompare() {
    // Retrieve complete database schema.
    $inspect = $this->inspector->inspect();

    foreach ($this->declared_schema as $t_name => $table) {
      // Check declared schema for inconsistencies.
      $this->checkTable($t_name, $table);

      // Fix inconsistencies which we do not want to show up as differences.
      $this->preprocessTableSchema($t_name, $table);

      // See if table exists in database and compare against schema if it does.
      if (!isset($inspect[$t_name])) {
        $this->result->addMissingTable($t_name, $table);
      }
      else {
        $this->compareTable($t_name, $table, $inspect[$t_name]);
        unset($inspect[$t_name]);
      }
    }

    // Mark remaining tables as extra tables, which are only in the database.
    foreach ($inspect as $name => $table) {
      $this->result->addExtraTable($name, $table);
    }
  }

  /**
   * Checks a given table schema definition for inconsistencies, and adds
   * warnings to the result field.
   *
   * Currently implemented error checks:
   * - fields need to be defined on all tables.
   * - column type and default type must match.
   * - 'text' and 'blob' columns cannot have a default value.
   * - primary keys must be 'not null'
   *
   * @todo Checks to consider adding:
   * - All type serial columns must be in an index or key.
   * - All columns in a primary or unique key must be NOT NULL.
   *
   * @param $t_name
   *   The table name.
   * @param $table
   *   The table schema definition.
   */
  protected function checkTable($t_name, $table) {
    // Error check: fields need to be defined on all tables.
    if (!isset($table['fields']) || !is_array($table['fields'])) {
      $this->result->addWarning(t('Table %table: Missing or invalid \'fields\' array.', array('%table' => $t_name)));
    }
    else {
      foreach ($table['fields'] as $c_name => $col) {
        // Error check: column type and default type must match
        switch ($col['type']) {
          case 'int':
          case 'float':
          case 'numeric':
            if (isset($col['default']) &&
              (!is_numeric($col['default']) || is_string($col['default']))
            ) {
              $this->result->addWarning(t('%table.%column is type %type but its default %default is PHP type %phptype', array(
                '%table' => $t_name,
                '%column' => $c_name,
                '%type' => $col['type'],
                '%default' => $col['default'],
                '%phptype' => gettype($col['default'])
              )));
            }
            break;

          default:
            if (isset($col['default']) && !is_string($col['default'])) {
              $this->result->addWarning(t('%table.%column is type %type but its default %default is PHP type %phptype', array(
                '%table' => $t_name,
                '%column' => $c_name,
                '%type' => $col['type'],
                '%default' => $col['default'],
                '%phptype' => gettype($col['default'])
              )));
            }
            break;
        }

        // Error check: 'text' and 'blob' columns cannot have a default value
        switch ($col['type']) {
          case 'text':
          case 'blob':
            if (isset($col['default'])) {
              $this->result->addWarning(t('%table.%column is type %type and may not have a default value', array(
                '%table' => $t_name,
                '%column' => $c_name,
                '%type' => $col['type']
              )));
            }
            break;
        }
      }
    }

    // Error check: primary keys must be 'not null'
    if (isset($table['primary key'])) {
      $keys = db_field_names($table['primary key']);
      foreach ($keys as $key) {
        if (!isset($table['fields'][$key]['not null']) || $table['fields'][$key]['not null'] != TRUE) {
          $this->result->addWarning(t('%table.%column is part of the primary key but is not specified to be \'not null\'.', array(
            '%table' => $t_name,
            '%column' => $key
          )));
        }
      }
    }
  }

  /**
   * Make sure the given schema is consistent.
   *
   * @param $t_name
   * @param $table
   */
  protected function preprocessTableSchema($t_name, &$table) {
    $_db_type = db_driver();

    $primary_key = empty($table['primary key']) ? array() : $table['primary key'];
    foreach ($table['fields'] as $f_name => &$field) {
      // MySQL Specification: If the column is defined as part of a PRIMARY
      // KEY but not explicitly as NOT NULL, MySQL creates it as a NOT NULL
      // column (because PRIMARY KEY columns must be NOT NULL), but also
      // assigns it a DEFAULT clause using the implicit default value.
      // @see http://dev.mysql.com/doc/refman/5.5/en/data-type-defaults.html
      // @todo Remove once this is fixed in core.
      // @see https://www.drupal.org/node/2394069
      if (in_array($f_name, $primary_key)) {
        $field['not null'] = TRUE;
      }

      // Many Schema types can map to the same engine type (e.g. in
      // PostgresSQL, text:{small,medium,big} are all just text).  When
      // we inspect the database, we see the common type, but the
      // reference we are comparing against can have a specific type.
      // We therefore run the reference's specific type through the
      // type conversion cycle to get its common type for comparison.
      //
      // Sadly, we need a special-case hack for 'serial'.
      $serial = ($field['type'] == 'serial' ? TRUE : FALSE);
      $name = isset($table['name']) ? $table['name'] : $t_name;
      $dbtype = schema_engine_type($field, $name, $f_name);
      list($field['type'], $field['size']) = schema_schema_type($dbtype, $name, $f_name);
      if ($serial) {
        $field['type'] = 'serial';
      }

      // If an engine-specific type is specified, use it.  XXX $inspect
      // will contain the schema type for the engine type, if one
      // exists, whereas dbtype_type contains the engine type.
      if (isset($field[$_db_type . '_type'])) {
        $field['type'] = $field[$_db_type . '_type'];
      }

      // Column comments are trimmed to a specific length by the database schema
      // layer. Make sure we match the trimmed value, so we can properly compare
      // actual and declared value.
      if (!empty($field['description'])) {
        $field['description'] = $this->inspector->prepareColumnComment($field['description'], FALSE);
      }
    }
  }

  /**
   * Compares a table's declared and actual schema.
   *
   * @param $ref_name
   *   The table name.
   * @param $ref
   *   The table schema definition.
   * @param $inspect
   *   The actual database schema.
   */
  protected function compareTable($ref_name, $ref, $inspect) {
    $result = $this->result->getTableComparison($ref_name, $ref);

    $result->setActualTableComment($inspect['description']);

    // DB layer trims table comments to a maximum length; in order to be able to
    // properly determine a difference between the declared and actual comment,
    // we need to do the same for the declared table comment.
    $declared = $result->getDeclaredTableComment();
    $declared = $this->inspector->prepareTableComment($declared, FALSE);
    $result->setDeclaredTableComment($declared);

    // List of column keys to compare. The schema definition can contain
    // additional keys (e.g. serialize) which have not effect on the actual
    // database schema.
    $col_keys = array_flip(array(
      'description',
      'type',
      'size',
      'not null',
      'default',
      'length',
      'unsigned',
      'precision',
      'scale',
      'binary',
    ));

    foreach ($ref['fields'] as $colname => $col) {
      // Check if field exists in database.
      if (!isset($inspect['fields'][$colname])) {
        $result->addMissingColumn($colname, $col);
        continue;
      }

      // Account for schemas that contain unnecessary 'default' => NULL
      if (isset($col['default']) && is_null($col['default']) && !isset($inspect['fields'][$colname]['default'])) {
        unset($col['default']);
      }

      // Limit the declared schema to the set of keys defined above.
      $col = array_intersect_key($col, $col_keys);

      // Compare column schema keys.
      $kdiffs = array();
      foreach ($col_keys as $key => $val) {
        if (!(
          // First part tests that item exists for both and has same value in both places
          (isset($col[$key]) && !is_null($col[$key]) && $col[$key] !== FALSE
            && isset($inspect['fields'][$colname][$key]) && $inspect['fields'][$colname][$key] !== FALSE
            && $col[$key] == $inspect['fields'][$colname][$key])
          // Second test is that it does not exist or exists but is null in both places
          || ((!isset($col[$key]) || is_null($col[$key]) || $col[$key] === FALSE)
            && (!isset($inspect['fields'][$colname][$key]) || $inspect['fields'][$colname][$key] === FALSE)))
        ) {
          // One way or another, difference between the two so note it to explicitly identify it later.
          $kdiffs[] = $key;
        }
      }
      if (count($kdiffs) != 0) {
        $result->addColumnDifferences($colname, $kdiffs, $col, $inspect['fields'][$colname]);
      }
      unset($inspect['fields'][$colname]);
    }

    // Keep track of extra columns in database.
    foreach ($inspect['fields'] as $colname => $col) {
      $result->addExtraColumn($colname, $col);
    }

    if (isset($ref['primary key'])) {
      if (!isset($inspect['primary key'])) {
        $result->addMissingPrimaryKey($ref['primary key']);
      }
      elseif ($ref['primary key'] !== $inspect['primary key']) {
        $result->addPrimaryKeyDifference($ref['primary key'], $inspect['primary key']);
      }
    }
    elseif (isset($inspect['primary key'])) {
      $result->addExtraPrimaryKey($inspect['primary key']);
    }

    foreach (array('unique keys', 'indexes') as $type) {
      if (isset($ref[$type])) {
        foreach ($ref[$type] as $keyname => $key) {
          if (!isset($inspect[$type][$keyname])) {
            $result->addMissingIndex($keyname, $type, $key);
            continue;
          }
          // $key is column list
          if ($key !== $inspect[$type][$keyname]) {
            $result->addIndexDifferences($keyname, $type, $key, $inspect[$type][$keyname]);
          }
          unset($inspect[$type][$keyname]);
        }
      }
      if (isset($inspect[$type])) {
        foreach ($inspect[$type] as $keyname => $col) {
          $result->addExtraIndex($keyname, $type, $col);
        }
      }
    }
  }

  /**
   * Reset the result object.
   */
  public function reset() {
    $this->result = NULL;
  }

}
