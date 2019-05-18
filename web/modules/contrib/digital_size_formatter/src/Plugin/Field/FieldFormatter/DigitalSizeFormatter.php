<?php

namespace Drupal\digital_size_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'digital_size' formatter.
 *
 * @FieldFormatter(
 *   id = "digital_size",
 *   label = @Translation("Digital size"),
 *   field_types = {
 *     "integer",
 *     "decimal",
 *     "float",
 *   }
 * )
 */
class DigitalSizeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Formatted as digital size (2.3MB, 1KB, 3GB, ...)');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      // If the field is decimal or float, convert it to integer.
      $value = (int) round($item->value);
      $elements[$delta] = ['#markup' => format_size($value)];
    }
    return $elements;
  }

}
