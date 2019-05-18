<?php

/**
 * @file
 * Contains \Drupal\pe_migrate\Plugin\migrate\source\DemoUniSection.
 */

namespace Drupal\pe_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 *
 * @MigrateSource(
 *   id = "demo_uni_section"
 * )
 */
class DemoUniSection extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('pe_migrate_node_section', 'pens')
      ->fields('pens', ['title', 'body', 'field_image_sec', 'field_code', 'field_parent_section_id', 'field_edu_id', 'field_section_type_id', 'field_founding_date', 'field_defunct_date', ' field_history', 'field_head_title', 'field_ahead_title', 'field_head', 'field_ahead', 'path'])
      // We sort this way to ensure parent sections are imported first.
      ->orderBy('field_parent_section_id', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'title' => $this->t('Title'),
      'body' => $this->t('Description'),
      'path' => $this->t('Path'),
      'field_image_sec' => $this->t('Image'),
      'field_founding_date' => $this->t('Founding date'),
      'field_defunct_date' => $this->t('Defunct date'),
      'field_code' => $this->t('Code'),
      'field_parent_section_id' => $this->t('Parent section'),
      'field_history' => $this->t('History'),
      'field_section_type_id' => $this->t('Section type - academic/non-academic'),
      'field_edu_id' => $this->t('Educational instituition'),
      'field_head_title' => $this->t('Head title'),
      'field_head' => $this->t('Head'),
      'field_ahead_title' => $this->t('Assistant/Deputy head title'),
      'field_ahead' => $this->t('Assistant/Deputy head'),
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
        'alias' => 'pens',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!$value = $row->getSourceProperty('field_head')) {
      $row->setSourceProperty('field_head', 0);
    }
    if (!$value = $row->getSourceProperty('field_ahead')) {
      $row->setSourceProperty('field_ahead', 0);
    }

    if (!$value = $row->getSourceProperty('field_parent_section_id')) {
      $row->setSourceProperty('field_parent_section_id', 0);
    }
    else {
      // @todo: migration of parent field doesn't work for pe_section nodes.
      $parent_section_id =  db_select('node_field_data', 'n')
        ->fields('n', ['nid'])
        ->condition('n.title', $row->getSourceProperty('field_parent_section_id'))
        ->execute()
        ->fetchField();
      $row->setSourceProperty('field_parent_section_id', $parent_section_id);
    }

    return parent::prepareRow($row);
  }
}
