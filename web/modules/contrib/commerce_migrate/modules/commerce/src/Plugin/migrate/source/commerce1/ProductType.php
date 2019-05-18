<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Row;

/**
 * Gets Commerce 1 commerce_product_type data from database.
 *
 * @MigrateSource(
 *   id = "commerce1_product_type",
 *   source_module = "commerce_product"
 * )
 */
class ProductType extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'type' => t('Type'),
      'name' => t('Name'),
      'description' => t('Description'),
      'help' => t('Help'),
      'revision' => t('Revision'),
      'line_item_type' => t('Line item type'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type']['type'] = 'string';
    $ids['type']['alias'] = 'pt';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Add the line item type for this product type, if it exists.
    $type = $row->getSourceProperty('type');
    $data = $this->select('field_config_instance', 'fci')
      ->fields('fci', ['data'])
      ->condition('fci.bundle', $type)
      ->condition('data', '%line_item_type%', 'LIKE')
      ->execute()
      ->fetchCol();
    $data = empty($data) ?: unserialize($data[0]);
    $line_item_type = isset($data['display']['default']['settings']['line_item_type']) ? $data['display']['default']['settings']['line_item_type'] : '';
    $row->setSourceProperty('line_item_type', $line_item_type);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('commerce_product_type', 'pt')->fields('pt');
    return $query;
  }

}
