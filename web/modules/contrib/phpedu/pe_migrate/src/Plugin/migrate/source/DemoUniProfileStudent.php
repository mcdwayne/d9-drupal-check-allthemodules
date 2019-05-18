<?php

/**
 * @file
 * Contains \Drupal\pe_migrate\Plugin\migrate\source\DemoUniProfileStudent.
 */

namespace Drupal\pe_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * @MigrateSource(
 *   id = "demo_uni_profile_student"
 * )
 */
class DemoUniProfileStudent extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('pe_migrate_profile_student', 'pepst')
      ->fields('pepst', ['name', 'uuid', 'field_acad_program_title', 'field_level_title', 'field_entry_year_title', 'field_matriculation_number', 'field_entry_mode_title'])
      ->orderBy('name', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'name' => $this->t('Username'),
      'uuid' => $this->t('UUID'),
      'field_acad_program_title' => $this->t('Academic progam'),
      'field_level_title' => $this->t('Level'),
      'field_entry_year_title' => $this->t('Year of entry'),
      'field_matriculation_number' => $this->t('Matriculation/student number'),
      'field_entry_mode_title' => $this->t('Mode of entry'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'name' => [
        'type' => 'string',
        'alias' => 'pepst',
      ],
    ];
  }
}
