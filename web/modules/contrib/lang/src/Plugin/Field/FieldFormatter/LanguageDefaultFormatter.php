<?php

/**
 * @file
 * Definition of Drupal\lang\Plugin\field\formatter\LanguageDefaultFormatter.
 */

namespace Drupal\lang\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'country' formatter.
 *
 * @FieldFormatter(
 *   id = "language_default",
 *   module = "lang",
 *   label = @Translation("Language"),
 *   field_types = {
 *     "lang"
 *   }
 * )
 */
class LanguageDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    $languages = getLanguageOptions();
    foreach ($items as $delta => $item) {
      if (isset($languages[$item->value])) {
        $elements[$delta] = array('#markup' => $languages[$item->value]);
      }
    }
    return $elements;
  }
}
