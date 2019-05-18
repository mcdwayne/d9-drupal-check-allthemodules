<?php

/**
 * @file
 * Contains \Drupal\pe_migrate\Plugin\migrate\source\DemoUniAcadTerm.
 */

namespace Drupal\pe_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 *
 * @MigrateSource(
 *   id = "demo_uni_acad_term"
 * )
 */
class DemoUniAcadTerm extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('pe_migrate_node_acad_term', 'peat')
      ->fields('peat', ['title', 'field_term', 'field_acad_year', 'field_start_date', 'field_end_date', 'body'])
      ->orderBy('title', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'title' => $this->t('Title'),
      'field_start_date' => $this->t('Start date'),
      'field_end_date' => $this->t('End date'),
      'field_acad_year' => $this->t('Academic year'),
      'field_term' => $this->t('Term'),
      'body' => $this->t('Body'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'title' => [
        'type' => 'string',
        'alias' => 'peat',
      ],
    ];
  }
}
