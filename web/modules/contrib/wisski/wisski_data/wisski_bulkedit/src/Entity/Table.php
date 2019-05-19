<?php

/**
 * @file
 * Contains \Drupal\wisski_bulkedit\Entity\Table.
 */

namespace Drupal\wisski_bulkedit\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\wisski_bulkedit\TableInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Database\SchemaObjectExistsException;
/**
 * Defines the WissKI Salz Table entity.
 * 
 * @ConfigEntityType(
 *   id = "wisski_bulkedit_table",
 *   label = @Translation("WissKI Bulkedit Table"),
 *   handlers = {
 *     "list_builder" = "Drupal\wisski_bulkedit\TableListBuilder",
 *     "form" = {
 *       "add" = "Drupal\wisski_bulkedit\Form\Table\AddForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *   },
 *   config_prefix = "wisski_bulkedit_table",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/wisski/bulkedit/table/{wisski_bulkedit_table}",
 *     "add-form" = "/admin/config/wisski/bulkedit/table/add",
 *     "delete-form" = "/admin/config/wisski/bulkedit/table/{wisski_bulkedit_table}/delete",
 *     "collection" = "/admin/config/wisski/bulkedit/table"
 *   },
 *   config_export = {
 *     "id",
 *     "tableName",
 *     "label",
 *     "timestamp",
 *     "schema"
 *   }
 * )
 */
class Table extends ConfigEntityBase implements TableInterface {
  
  const TABLE_PREFIX = 'wisski_bulkedit_table_';

  /**
   * The ID of the table
   *
   * @var string
   */
  protected $id;

  /**
   * The ID of the table
   *
   * @var string
   */
  protected $tableName;

  /**
   * The Table label.
   *
   * @var string
   */
  protected $label;

  /**
   * The table creation timestamp.
   *
   * @var integer/long
   */
  protected $timestamp;

  /**
   * The schema for the table.
   *
   * @var array
   */
  protected $schema;

  
  public function timestamp() {
    return $this->timestamp;
  }


  public function tableName($with_drupal_prefix = TRUE) {
    if (!$this->tableName) {
      $this->tableName = self::TABLE_PREFIX . $this->id;
    }
    $name = $this->tableName;
    if ($with_drupal_prefix) {
      $name = \Drupal::database()->tablePrefix($name) . $name;
    }
    return $name;
  }


  public function countColumns() {
    if (!empty($this->schema) && $this->tableExists()) {
      return count($this->schema['fields']);
    }
    return NULL;
  }

  public function countRows() {
    if (!$this->tableExists()) return NULL;
    $count = $this->getDbConnection()
             ->select($this->tableName(), 't')
             ->countQuery()
             ->execute()
             ->fetchField();
    return $count ?: 0;
  }

  public function getRowSize($row = NULL) {
    if ($row === NULL) {
      $row = 0;
    }
    if (!empty($this->schema)) {
      $field = NULL;
      if (is_string($row)) {
        if (isset($this->schema['fields'][$row])) {
          $field = $this->schema['fields'][$row];
        }
      }
      elseif (is_int($row)) {
        $i = 0;
        foreach ($this->schema['fields'] as $f) {
          if ($i == $row) {
            $field = $f;
            break;
          }
        }
      }
      if ($field) {
        if (isset($field['length'])) {
          return $field['length'];
        }
        elseif (isset($field['size'])) {
          return $field['size'];
        }
      }
    }
    return NULL;
  }

  
  public function getDbConnection() {
    return \Drupal::database();
  }
  

  public function makeTable($schema) {
    $dbschema = $this->getDbConnection()->schema();
    try {
      $dbschema->createTable($this->tableName(), $schema);
    } catch (SchemaObjectExistsException $e) {
      return FALSE;
    }
    $this->schema = $schema;
    $this->timestamp = time();
    $this->save();
    return TRUE;
  }
  
  
  public function tableExists() {
    $dbschema = $this->getDbConnection()->schema();
    return $dbschema->tableExists($this->tableName());
  }
  
  

  protected function dropTable() {
    $dbschema = $this->getDbConnection()->schema();
    return $dbschema->dropTable($this->tableName()); 
  }

  
  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->dropTable();
    return parent::delete();
  }
  
}
