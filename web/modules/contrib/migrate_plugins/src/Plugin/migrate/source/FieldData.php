<?php

namespace Drupal\migrate_plugins\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Provides a D7 'FieldData' migrate source.
 *
 * @MigrateSource(
 *  id = "d7_field_data"
 * )
 */
class FieldData extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

    // The field_name config is required to know what table to query.
    if (!isset($this->configuration['field_name'])) {
      throw new MigrateException("The source field_name needs to be configured.");
    }

    $table = 'field_data_' . $this->configuration['field_name'];

    $query = $this->select($table, 'fd')
      ->fields('fd');

    if (!isset($this->configuration['entity_type'])) {
      $query->condition('entity_type', $this->configuration['entity_type']);
    }

    if (!isset($this->configuration['bundle'])) {
      $query->condition('bundle', $this->configuration['bundle']);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'entity_type' => $this->t('The entity type this data is attached to.'),
      'bundle' => $this->t('The field instance bundle to which this row belongs, used when deleting a field instance.'),
      'deleted' => $this->t('A boolean indicating whether this data item has been deleted.'),
      'entity_id' => $this->t('The entity id this data is attached to.'),
      'revision_id' => $this->t('The entity revision id this data is attached to, or NULL if the entity type is not versioned.'),
      'language' => $this->t('The language for this data item.'),
      'delta' => $this->t('The sequence number for this data item, used for multi-value fields.'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_id' => [
        'type' => 'integer',
        'alias' => 'fd',
      ],
      'delta' => [
        'type' => 'integer',
        'alias' => 'fd',
      ],
    ];
  }

}
