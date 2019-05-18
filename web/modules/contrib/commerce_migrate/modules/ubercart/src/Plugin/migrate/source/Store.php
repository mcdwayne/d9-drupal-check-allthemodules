<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\Variable;

/**
 * Gets the Ubercart store data.
 *
 * @MigrateSource(
 *   id = "uc_store",
 *   source_module = "uc_store"
 * )
 */
class Store extends Variable {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() +
      [
        'uid' => $this->t('User ID'),
        'country_iso_code_2' => $this->t('Country ISO code 2'),
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $store_owner = $row->getSourceProperty('uc_store_owner');
    $query = $this->select('users', 'u')
      ->fields('u', ['uid'])
      ->condition('name', $store_owner);
    $uid = $query->execute()->fetchField();
    $row->setSourceProperty('uid', $uid);
    $country = $row->getSourceProperty('uc_store_country');
    // Get the country iso code 2 for this country.
    $query = $this->select('uc_countries', 'ucc')
      ->fields('ucc', ['country_iso_code_2'])
      ->condition('country_id', $country);
    $country_iso_code_2 = $query->execute()->fetchField();
    $row->setSourceProperty('country_iso_code_2', $country_iso_code_2);
    return parent::prepareRow($row);
  }

}
