<?php

namespace Drupal\commerce_migrate_magento\Plugin\migrate\source\magento2;

use Drupal\migrate\Row;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Gets the destination site default language.
 *
 * @MigrateSource(
 *   id = "magento2_csv"
 * )
 */
class Magento extends CSV {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get the destination site default language.
    $language = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $row->setSourceProperty('destination_default_langcode', $language);
    return parent::prepareRow($row);
  }

}
