<?php

/**
 * @file
 * Contains \Drupal\pe_migrate\Plugin\migrate\source\DemoUniProfileStaff.
 */

namespace Drupal\pe_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * @MigrateSource(
 *   id = "demo_uni_profile_staff"
 * )
 */
class DemoUniProfileStaff extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('pe_migrate_profile_staff', 'pepsf')
      ->fields('pepsf', ['name', 'roles', 'uuid', 'field_section_title', 'field_position_title', 'field_academic', 'field_staff_number', 'field_employment_year_title'])
      ->orderBy('name', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'name' => $this->t('Username'),
      'roles' => $this->t('Roles'),
      'uuid' => $this->t('UUID'),
      'field_section_title' => $this->t('Faculty/Department'),
      'field_position_title' => $this->t('Position'),
      'field_academic' => $this->t('Academic staff?'),
      'field_staff_number' => $this->t('Staff number'),
      'field_employment_year_title' => $this->t('Year of employment'),
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
        'alias' => 'pepsf',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!$value = $row->getSourceProperty('field_position_title')) {
      $row->setSourceProperty('field_position_title', 0);
    }

    return parent::prepareRow($row);
  }
}
