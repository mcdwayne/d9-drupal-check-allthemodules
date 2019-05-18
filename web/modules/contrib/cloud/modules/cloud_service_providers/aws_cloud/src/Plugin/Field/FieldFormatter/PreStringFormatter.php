<?php

namespace Drupal\aws_cloud\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'pre_string_formatter' formatter.
 *
 * This formatter use PRE tag to surround content.
 *
 * @FieldFormatter(
 *   id = "pre_string_formatter",
 *   label = @Translation("PRE tag string formatter"),
 *   field_types = {
 *     "string_long",
 *   }
 * )
 */
class PreStringFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => '<pre>' . $item->value . '</pre>',
      ];
    }

    return $elements;
  }

}
