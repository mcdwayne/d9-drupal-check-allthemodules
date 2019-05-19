<?php

namespace Drupal\vat_number\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_example_simple_text' formatter.
 *
 * @FieldFormatter(
 *   id = "vat_formatter",
 *   module = "vat_number",
 *   label = @Translation("Simple formatter for the VAT Number"),
 *   field_types = {
 *     "vat_number"
 *   }
 * )
 */
class VatNumberFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $item->value,
      ];
    }

    return $elements;
  }

}
