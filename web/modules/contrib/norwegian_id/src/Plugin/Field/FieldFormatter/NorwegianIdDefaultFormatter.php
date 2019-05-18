<?php

namespace Drupal\norwegian_id\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'norwegian_id_default' formatter.
 *
 * @FieldFormatter(
 *   id = "norwegian_id_default",
 *   label = @Translation("Text formatter for Norwegian personal ID"),
 *   field_types = {
 *     "norwegian_id"
 *   }
 * )
 */
class NorwegianIdDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return array
   *   The textual output generated as a render array.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return [
      '#type'     => 'inline_template',
      '#template' => '{{ value|nl2br }}',
      '#context'  => ['value' => $item->value],
    ];
  }

}
