<?php

namespace Drupal\raw_string_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'raw_string' formatter.
 *
 * @FieldFormatter(
 *   id = "raw_string",
 *   label = @Translation("Raw text"),
 *   field_types = {
 *     "string_long",
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class RawStringFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'inline_template',
        '#template' => '{{ value|raw }}',
        '#context' => ['value' => $item->value],
      ];
    }

    return $elements;
  }

}
