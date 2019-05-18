<?php

namespace Drupal\migrate_override\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'override_formatter_default' formatter.
 *
 * Displays nothing.
 *
 * @FieldFormatter(
 *   id = "override_formatter_default",
 *   label = @Translation("Migrate override formatter"),
 *   field_types = {
 *     "migrate_override_field_item"
 *   }
 * )
 */
class MigrateOverrideFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    return [];
  }

}
