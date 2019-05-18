<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1;

use Drupal\migrate\Row;
use Drupal\taxonomy\Plugin\migrate\source\d7\Vocabulary as CoreVocabulary;

/**
 * Drupal 7 vocabularies source from database.
 *
 * @MigrateSource(
 *   id = "commerce1_attribute",
 *   source_module = "taxonomy"
 * )
 */
class Vocabulary extends CoreVocabulary {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + [
      'attribute' => $this->t('Attribute flag'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get all the taxonomy term reference field instances.
    $query = $this->select('field_config_instance', 'fci')
      ->fields('fci')
      ->fields('fc', ['type'])
      ->condition('fc.active', 1)
      ->condition('fc.storage_active', 1)
      ->condition('fc.deleted', 0)
      ->condition('fci.deleted', 0)
      ->condition('fci.entity_type', 'commerce_product')
      ->condition('fc.type', 'taxonomy_term_reference');
    $query->join('field_config', 'fc', 'fci.field_id = fc.id');
    $query->addField('fc', 'data', 'fc_data');
    $results = $query->execute()->fetchAll();

    // Set attribute flag to false. The process will use this to skip the row.
    $row->setSourceProperty('attribute', FALSE);
    foreach ($results as $key => $value) {
      $results[$key]['data'] = unserialize($value['data']);
      $results[$key]['fc_data'] = unserialize($value['fc_data']);
      $allowed_values = $results[$key]['fc_data']['settings']['allowed_values'][0]['vocabulary'];
      // Mark the row as an attribute row when the row vocabulary machine name
      // is used by the field and it uses an option widget.
      if ($allowed_values === $row->getSourceProperty('machine_name') &&
        ($results[$key]['data']['widget']['type'] == 'options_select')) {
        $row->setSourceProperty('attribute', TRUE);
        break;
      }
    }

    return parent::prepareRow($row);
  }

}
