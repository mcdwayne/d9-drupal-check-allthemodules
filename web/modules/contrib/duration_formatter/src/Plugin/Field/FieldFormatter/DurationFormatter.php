<?php

namespace Drupal\duration_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Field formatter for displaying durations.
 *
 * @FieldFormatter(
 *   id = "duration",
 *   label = @Translation("Duration"),
 *   field_types = {
 *     "integer",
 *     "float",
 *     "decimal",
 *   }
 * )
 */
class DurationFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => duration_formatter_format($item->value),
      ];
    }

    return $elements;
  }

}
