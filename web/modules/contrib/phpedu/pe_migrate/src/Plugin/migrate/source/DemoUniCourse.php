<?php

/**
 * @file
 * Contains \Drupal\pe_migrate\Plugin\migrate\source\DemoUniCourse.
 */

namespace Drupal\pe_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 *
 * @MigrateSource(
 *   id = "demo_uni_course"
 * )
 */
class DemoUniCourse extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('pe_migrate_node_course', 'penc')
      ->fields('penc', ['title', 'body', 'field_image', 'field_section_id', 'field_code', 'field_delivery_method', 'field_hours', 'field_credits', 'field_remarks'])
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
      'field_section_id' => $this->t('Section'),
      'field_delivery_method' => $this->t('Delivery method'),
      'field_hours' => $this->t('Hours'),
      'field_credits' => $this->t('Credits'),
      'field_remarks' => $this->t('Remarks'),
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
        'alias' => 'penc',
      ],
    ];
  }

}
