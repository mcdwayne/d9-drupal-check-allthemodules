<?php

/**
 * @file
 * Contains \Drupal\pe_migrate\Plugin\migrate\source\DemoUniTerm.
 */

namespace Drupal\pe_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 *
 * @MigrateSource(
 *   id = "demo_uni_term"
 * )
 */
class DemoUniTerm extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('pe_migrate_taxonomy_term', 'pet')
      ->fields('pet', ['name', 'description', 'format', 'weight', 'puuid', 'vocabulary_machine_name', 'parent'])
      // We sort this way to ensure parent terms are imported first.
      ->orderBy('vocabulary_machine_name', 'ASC')
      ->orderBy('parent', 'ASC');

  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'name' => $this->t('Term name'),
      'description' => $this->t('Description'),
      'format' => $this->t('Text format'),
      'weight' => $this->t('Weight'),
      'vocabulary_machine_name' => $this->t('Vocabulary ID'),
      'parent' => $this->t('Parent term'),

      // These values are not currently migrated - it's OK to skip fields you
      // don't need.
      'puuid' => $this->t('Term UUID'),

    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'puuid' => [
        'type' => 'string',
        'alias' => 'pet',
      ],
    ];
  }

}
