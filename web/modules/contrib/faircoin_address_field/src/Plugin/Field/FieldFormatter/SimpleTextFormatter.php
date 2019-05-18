<?php

/**
 * @file
 * Drupal\faircoin_address_field\Plugin\Field\FieldFormatter\SimpleTextFormatter.
 */

namespace Drupal\faircoin_address_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
// Use Drupal\Component\Utility\SafeMarkup;
/**
 * Plugin implementation of the 'faircoin_address_field_code_text' formatter.
 *
 * @FieldFormatter(
 *   id = "faircoin_address_field_code_text",
 *   module = "faircoin_address_field",
 *   label = @Translation("Simple text-based formatter"),
 *   field_types = {
 *     "faircoin_address"
 *   }
 * )
 */
class SimpleTextFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        // We create a render array to produce the desired markup,
        // See theme_html_tag().
        '#type' => 'html_tag',
        '#tag' => 'code',
        '#attributes' => array(
          'style' => 'font-family: Courier, monospace',
        ),
        // Must be '#value' => SafeMarkup::checkPlain($item->value),
        '#value' => $item->value,
      );
    }

    return $elements;
  }

}
