<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Base class for Ubercart tax type source plugins.
 */
class TaxTypeBase extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('uc_taxes', 'ut')->fields('ut');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('TaxType ID'),
      'name' => $this->t('TaxType Name'),
      'rate' => $this->t('TaxType Rate'),
      'shippable' => $this->t('Shippable'),
      'taxed_product_types' => $this->t('Taxed product types'),
      'taxed_line_item' => $this->t('Taxed line item'),
      'weight' => $this->t('Weight'),
      'country_iso_code_2' => $this->t('Country 2 character code'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('taxed_product_types', unserialize($row->getSourceProperty('taxed_product_types')));
    $row->setSourceProperty('taxed_line_items', unserialize($row->getSourceProperty('taxed_line_items')));

    $country = $this->variableGet('uc_store_country', NULL);
    // Get the country iso code 2 for this country.
    $query = $this->select('uc_countries', 'ucc')
      ->fields('ucc', ['country_iso_code_2'])
      ->condition('country_id', $country);
    $country_iso_code_2 = $query->execute()->fetchField();
    $row->setSourceProperty('country_iso_code_2', $country_iso_code_2);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'ut',
      ],
    ];
  }

}
