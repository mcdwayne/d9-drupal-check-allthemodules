<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Ubercart 7 field source from database.
 *
 * This is a modified copy of the core d7_field source plugin. The changes are
 * the addition of initializeIterator() so that rows can be added when a field
 * exists on a product node and any other entity. The added rows are solely to
 * create such a field on a Commerce 2 commerce_product entity.
 *
 * @MigrateSource(
 *   id = "uc7_field",
 *   source_module = "field_sql_storage"
 * )
 */
class Field extends DrupalSqlBase {

  /**
   * Product node types.
   *
   * @var array
   */
  protected $productTypes = [];

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $this->productTypes = $this->getProductTypes();

    $results = $this->prepareQuery()->execute();
    $rows = [];
    foreach ($results as $result) {
      // Get all the instances of this field.
      $field_name = $result['field_name'];
      // Get all the instances of this field.
      $query = $this->select('field_config_instance', 'fci')
        ->fields('fci', ['bundle'])
        ->condition('fc.active', 1)
        ->condition('fc.storage_active', 1)
        ->condition('fc.deleted', 0)
        ->condition('fci.deleted', 0)
        ->condition('fci.entity_type', 'node');
      $query->join('field_config', 'fc', 'fci.field_id = fc.id');
      $query->condition('fci.field_name', $field_name);
      $node_bundles = $query->execute()->fetchCol();

      // Determine if the field is on both a product type and node, or just one
      // of product type or node.
      $product_node_count = 0;
      foreach ($node_bundles as $bundle) {
        if (in_array($bundle, $this->productTypes)) {
          $product_node_count++;
        }
      }

      $node_count = 0;
      foreach ($node_bundles as $bundle) {
        if ($bundle === 'node') {
          $node_count++;
        }
      }
      if ($product_node_count > 0) {
        // If all bundles for this field are product types, then change the
        // entity type to 'commerce_product'.
        if ($product_node_count == count($node_bundles)) {
          $result['entity_type'] = 'commerce_product';
        }
        else {
          $add_row = $result;
          $add_row['entity_type'] = 'commerce_product';
          $rows[] = $add_row;
        }
      }
      $rows[] = $result;
    }
    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_config', 'fc')
      ->distinct()
      ->fields('fc')
      ->fields('fci', ['entity_type'])
      ->condition('fc.active', 1)
      ->condition('fc.storage_active', 1)
      ->condition('fc.deleted', 0)
      ->condition('fci.deleted', 0);
    $query->join('field_config_instance', 'fci', 'fc.id = fci.field_id');

    // If the Drupal 7 Title module is enabled, we don't want to migrate the
    // fields it provides. The values of those fields will be migrated to the
    // base fields they were replacing.
    if ($this->moduleExists('title')) {
      $title_fields = [
        'title_field',
        'name_field',
        'description_field',
        'subject_field',
      ];
      $query->condition('fc.field_name', $title_fields, 'NOT IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('The field ID.'),
      'field_name' => $this->t('The field name.'),
      'type' => $this->t('The field type.'),
      'module' => $this->t('The module that implements the field type.'),
      'active' => $this->t('The field status.'),
      'storage_type' => $this->t('The field storage type.'),
      'storage_module' => $this->t('The module that implements the field storage type.'),
      'storage_active' => $this->t('The field storage status.'),
      'locked' => $this->t('Locked'),
      'data' => $this->t('The field data.'),
      'cardinality' => $this->t('Cardinality'),
      'translatable' => $this->t('Translatable'),
      'deleted' => $this->t('Deleted'),
      'instances' => $this->t('The field instances.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row, $keep = TRUE) {
    foreach (unserialize($row->getSourceProperty('data')) as $key => $value) {
      $row->setSourceProperty($key, $value);
    }

    $instances = $this->select('field_config_instance', 'fci')
      ->fields('fci')
      ->condition('field_name', $row->getSourceProperty('field_name'))
      ->condition('entity_type', $row->getSourceProperty('entity_type'))
      ->execute()
      ->fetchAll();
    $row->setSourceProperty('instances', $instances);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'field_name' => [
        'type' => 'string',
        'alias' => 'fc',
      ],
      'entity_type' => [
        'type' => 'string',
        'alias' => 'fci',
      ],
    ];
  }

  /**
   * Helper to get the product types from the source database.
   *
   * @return array
   *   The product types.
   */
  protected function getProductTypes() {
    if (!empty($this->productTypes)) {
      return $this->productTypes;
    }
    $query = $this->select('node_type', 'nt')
      ->fields('nt', ['type'])
      ->condition('module', 'uc_product%', 'LIKE')
      ->distinct();
    $this->productTypes = [$query->execute()->fetchCol()];
    return reset($this->productTypes);
  }

}
