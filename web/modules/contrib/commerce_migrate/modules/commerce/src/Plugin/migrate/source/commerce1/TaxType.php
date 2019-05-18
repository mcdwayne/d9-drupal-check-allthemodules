<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Gets Commerce 1 commerce_tax_type data from database.
 *
 * @MigrateSource(
 *   id = "commerce1_tax_type",
 *   source_module = "commerce_tax"
 * )
 */
class TaxType extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('commerce_tax_rate', 'ctr')->fields('ctr');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => $this->t('TaxType Name'),
      'title' => $this->t('Title'),
      'display_title' => $this->t('Display title'),
      'description' => $this->t('Description'),
      'display_inclusive' => $this->t('Display inclusive'),
      'rate' => $this->t('TaxType Rate'),
      'type' => $this->t('Tax type'),
      'default_rules_component' => $this->t('Default rules component'),
      'module' => $this->t('Module'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Use the site default country for the tax types.
    $row->setSourceProperty('default_country', $this->variableGet('site_default_country', NULL));
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['name']['type'] = 'string';
    return $ids;
  }

}
