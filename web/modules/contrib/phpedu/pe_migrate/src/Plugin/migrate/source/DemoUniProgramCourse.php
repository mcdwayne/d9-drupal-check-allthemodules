<?php

/**
 * @file
 * Contains \Drupal\pe_migrate\Plugin\migrate\source\DemoUniProgramCourse.
 */

namespace Drupal\pe_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;


/**
 *
 * @MigrateSource(
 *   id = "demo_uni_program_course"
 * )
 */
class DemoUniProgramCourse extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('pe_migrate_node_program_course', 'penpc')
      ->fields('penpc', ['title', 'status', 'body', 'field_acad_program_id', 'field_course_id', 'field_acad_year_id', 'field_acad_term_id', 'field_course_weight', 'field_level', 'field_prereq_id', 'field_coreq_id', 'field_nonreq_id', 'field_antireq_id', 'field_teacher_id'])
      ->orderBy('title', 'ASC');

  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'title' => $this->t('Title'),
      'body' => $this->t('Description'),
      'status' => $this->t('Status'),
      'field_acad_program_id' => $this->t('Academic program'),
      'field_course_id' => $this->t('Course'),
      'field_acad_year_id' => $this->t('Academic year'),
      'field_acad_term_id' => $this->t('Academic term'),
      'field_course_weight' => $this->t('Course weight'),
      'field_level' => $this->t('Level'),
      'field_prereq_id' => $this->t('Pre-requisities'),
      'field_coreq_id' => $this->t('Co-requisities'),
      'field_nonreq_id' => $this->t('Non-requisities'),
      'field_antireq_id' => $this->t('Anti-requisities'),
      'field_teacher_id' => $this->t('Teacher(s)'),
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
        'alias' => 'penpc',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // teachers
    if ($value = $row->getSourceProperty('field_teacher_id')) {
      $row->setSourceProperty('field_teacher_id', explode('|', $value));
    }

    // courses
    if ($value = $row->getSourceProperty('field_prereq_id')) {
      $row->setSourceProperty('field_prereq_id', explode('|', $value));
    }
    else {
      $row->setSourceProperty('field_prereq_id', []);
    }

    if ($value = $row->getSourceProperty('field_coreq_id')) {
      $row->setSourceProperty('field_coreq_id', explode('|', $value));
    }
    else {
      $row->setSourceProperty('field_coreq_id', []);
    }

    if ($value = $row->getSourceProperty('field_nonreq_id')) {
      $row->setSourceProperty('field_nonreq_id', explode('|', $value));
    }
    else {
      $row->setSourceProperty('field_nonreq_id', []);
    }

    if ($value = $row->getSourceProperty('field_antireq_id')) {
      $row->setSourceProperty('field_antireq_id', explode('|', $value));
    }
    else {
      $row->setSourceProperty('field_antireq_id', []);
    }

    return parent::prepareRow($row);
  }

}
