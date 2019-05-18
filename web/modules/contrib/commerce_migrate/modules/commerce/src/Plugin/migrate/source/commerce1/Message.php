<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Gets the Commerce 1 currency data.
 *
 * @MigrateSource(
 *   id = "commerce1_message",
 *   source_module = "commerce_message"
 * )
 */
class Message extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('message', 'm')->fields('m');
    $query->leftJoin('message_type', 'mt', 'mt.name = m.type');
    $query->addField('mt', 'category');
    $query->addField('mt', 'name');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get Field API field values.
    $mid = $row->getSourceProperty('mid');
    foreach (array_keys($this->getFields('message', $row->getSourceProperty('type'))) as $field) {
      $row->setSourceProperty($field, $this->getFieldValues('message', $field, $mid));
    }
    $order_ref = $row->getSourceProperty('message_commerce_order');
    $row->setSourceProperty('target_id', $order_ref[0]['target_id']);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'mid' => t('Message ID'),
      'type' => t('Message type'),
      'arguments' => t('Arguments'),
      'uid' => t('UID'),
      'timestamp' => t('Message timestamp'),
      'language' => t('Language'),
      'target_id' => t('Target ID'),
      'name' => t('Message type name'),
      'category' => t('Message category'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['mid']['type'] = 'integer';
    return $ids;
  }

}
