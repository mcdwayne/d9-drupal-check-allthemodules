<?php

/**
 * @file
 * Contains \Drupal\pe_migrate\Plugin\migrate\source\DemoUniProfileMain.
 */

namespace Drupal\pe_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 *
 * @MigrateSource(
 *   id = "demo_uni_profile_main"
 * )
 */
class DemoUniProfileMain extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('pe_migrate_profile_main', 'pepm')
      ->fields('pepm', ['name', 'field_personal_title', 'field_firstname', 'field_middlename', 'field_lastname', 'field_birth_date', 'field_gender', 'field_marital_status', 'field_address__streetAddress', 'field_address__addressLocality', 'field_address__addressRegion', 'field_address__postalCode', 'field_address__addressCountry', 'field_mobile', 'field_phone', 'field_url__url', 'field_languages', 'field_interests'])
      ->orderBy('name', 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'name' => $this->t('Username'),
      'field_personal_title' => $this->t('Personal title'),
      'field_firstname' => $this->t('First name'),
      'field_middlename' => $this->t('Middle name'),
      'field_lastname' => $this->t('Last name'),
      'field_birth_date' => $this->t('Date of birth'),
      'field_gender' => $this->t('Gender'),
      'field_marital_status' => $this->t('Marital status'),
      'field_address__streetAddress' => $this->t('Address - Street'),
      'field_address__addressLocality' => $this->t('Address - City'),
      'field_address__addressRegion' => $this->t('Address - Region'),
      'field_address__postalCode' => $this->t('Address - Postcode'),
      'field_address__addressCountry' => $this->t('Address - Country'),
      'field_phone' => $this->t('Telephone number'),
      'field_mobile' => $this->t('Mobile number'),
      'field_url' => $this->t('Website'),
      'field_languages' => $this->t('Languages spoken'),
      'field_interests' => $this->t('Interests'),
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
        'alias' => 'pepm',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if ($value = $row->getSourceProperty('field_languages')) {
      $row->setSourceProperty('field_languages', explode('|', $value));
    }
    if ($value = $row->getSourceProperty('field_interests')) {
      $row->setSourceProperty('field_interests', explode('|', $value));
    }

    return parent::prepareRow($row);
  }

}
