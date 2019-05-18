<?php

namespace Drupal\data\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\data\DataException;

/**
 * Defines the Data Table entity.
 *
 * @ConfigEntityType(
 *   id = "data_table_config",
 *   label = @Translation("Table Configuration"),
 *   handlers = {
 *     "list_builder" = "Drupal\data\TableConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\data\Form\TableConfigForm",
 *       "edit" = "Drupal\data\Form\TableConfigForm",
 *       "delete" = "Drupal\data\Form\TableConfigDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\data\TableConfigHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "table_config",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/data/{data_table_config}",
 *     "add-form" = "/admin/structure/data/add",
 *     "edit-form" = "/admin/structure/data/{data_table_config}/edit",
 *     "delete-form" = "/admin/structure/data/{data_table_config}/delete",
 *     "collection" = "/admin/structure/data"
 *   }
 * )
 */
class TableConfig extends ConfigEntityBase implements TableConfigInterface {

  /**
   * {@inheritdoc}
   */
  public function exists() {
    return Database::getConnection()->schema()->tableExists($this->id());
  }

  /**
   * {@inheritdoc}
   */
  public function createTable() {
    $table_definition = array(
      'description' => t('Automatically created by data module on @time',
        array('@time' => date('Y/m/d H:i', REQUEST_TIME))),
      'fields' => array(),
    );
    $primary_keys = array();
    foreach ($this->table_schema as $field) {
      $table_definition['fields'][$field['name']] = array(
        'description' => $field['label'],
        'type' => $field['type'],
        'size' => $field['size'],
        'unsigned' => $field['unsigned'],
      );
      if ($field['length']) {
        $table_definition['fields'][$field['name']]['length'] = $field['length'];
      }
      if ($field['primary']) {
        $primary_keys[] = $field['name'];
      }
    }
    // @todo: non-primary index definition.
    if ($primary_keys) {
      $table_definition['primary key'] = $primary_keys;
    }
    Database::getConnection()->schema()->createTable($this->id(),
      $table_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    if ($this->isNew()) {
      if ($this->exists()) {
        throw new SchemaObjectExistsException(t('Table @name already exists.',
          array('@name' => $this->id())));
      }
      $this->createTable();
    }
    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    if (Database::getConnection()->schema()->dropTable($this->id())) {
      parent::delete();
    }
  }

}
