<?php

namespace Drupal\antisearch_filter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'antisearch_filter_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "antisearch_filter_formatter",
 *   label = @Translation("Antisearch filter formatter"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class AntisearchFilterFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $element[$delta] = [
        '#markup' => antisearch_filter($item->value),
        '#attached' => ['library' => ['antisearch_filter/antisearch-filter']],
      ];
    }

    return $element;
  }

}
