<?php

/**
 * @file
 * Contains \Drupal\pe_migrate\Plugin\migrate\source\DemoUniProgram.
 */

namespace Drupal\pe_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 *
 * @MigrateSource(
 *   id = "demo_uni_program"
 * )
 */
class DemoUniProgram extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('pe_migrate_node_program', 'penp')
      ->fields('penp', ['title', ' body', ' field_image', ' field_code', ' field_duration', ' field_duration_unit', ' field_section_id', ' field_term_program', ' field_term_time', ' field_term_program_type', ' field_requirements', ' field_career'])
      ->orderBy('title', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'title' => $this->t('Title'),
      'body' => $this->t('Description'),
      'field_image' => $this->t('Image'),
      'field_code' => $this->t('Code'),
      'field_duration' => $this->t('Duration'),
      'field_duration_unit' => $this->t('Duration unit'),
      'field_section_id' => $this->t('Section'),
      'field_term_program' => $this->t('Program'), // no mapping
      'field_term_time' => $this->t('Time - full/part-time'),
      'field_term_program_type' => $this->t('Progrm type'),
      'field_requirements' => $this->t('Requirements'),
      'field_career' => $this->t('Career'),
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
        'alias' => 'penp',
      ],
    ];
  }

}
