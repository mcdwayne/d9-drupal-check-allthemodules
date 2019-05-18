<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc7;

use Drupal\commerce_migrate_ubercart\Plugin\migrate\source\TaxTypeBase;

/**
 * Gets the Ubercart tax rates.
 *
 * @MigrateSource(
 *   id = "uc7_tax_type",
 *   source_module = "uc_taxes"
 * )
 */
class TaxType extends TaxTypeBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'display_include' => $this->t('Display include'),
      'inclusion_text' => $this->t('Inclusion text'),
    ];
    return parent::fields() + $fields;
  }

}
