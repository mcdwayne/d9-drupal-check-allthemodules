<?php

namespace Drupal\signature_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_signature_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_signature_field_formatter",
 *   module = "signature_field",
 *   label = @Translation("Signature formatter"),
 *   field_types = {
 *     "field_signature"
 *   }
 * )
 */
class SignatureFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        // We create a render array to produce the desired markup,
        // "<p style="color: #hexcolor">The color code ... #hexcolor</p>".
        // See theme_html_tag().
        '#type' => 'html_tag',
        '#tag' => 'img',
        '#attributes' => array('src' => $item->value),
      );
    }

    return $elements;
  }

}
