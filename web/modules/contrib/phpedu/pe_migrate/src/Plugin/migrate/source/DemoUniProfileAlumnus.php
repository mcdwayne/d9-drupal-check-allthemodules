<?php

/**
 * @file
 * Contains \Drupal\pe_migrate\Plugin\migrate\source\DemoUniProfileAlumnus.
 */

namespace Drupal\pe_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * @MigrateSource(
 *   id = "demo_uni_profile_alumnus"
 * )
 */
class DemoUniProfileAlumnus extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('pe_migrate_profile_alumnus', 'pepa')
      ->fields('pepa', ['name', 'uuid', 'field_alumni_association_member', 'field_acad_program_title', 'field_graduation_year_title'])
      ->orderBy('name', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'name' => $this->t('Username'),
      'uuid' => $this->t('UUID'),
      'field_alumni_association_member' => $this->t('Alumni Association Membership'),
      'field_acad_program_title' => $this->t('Academic program'),
      'field_graduation_year_title' => $this->t('Graduation year'),
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
        'alias' => 'pepa',
      ],
    ];
  }
}
