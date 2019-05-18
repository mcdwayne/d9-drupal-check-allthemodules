<?php

/**
 * @file
 * Contains \Drupal\pe_migrate\Plugin\migrate\source\DemoUniAcadYear.
 */

namespace Drupal\pe_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 *
 * @MigrateSource(
 *   id = "demo_uni_acad_year"
 * )
 */
class DemoUniAcadYear extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('pe_migrate_node_acad_year', 'peay')
      ->fields('peay', ['title', 'field_start_date', 'field_end_date', 'body'])
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
        'alias' => 'peay',
      ],
    ];
  }
}
