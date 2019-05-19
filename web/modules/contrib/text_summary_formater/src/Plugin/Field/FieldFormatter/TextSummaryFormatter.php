<?php

/**
 * @file
 * Contains \Drupal\text_summary_formatter\Plugin\Field\FieldFormatter\TextSummaryFormatter.
 */

namespace Drupal\text_summary_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'text_summary_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "text_summary_formatter",
 *   label = @Translation("Summary only"),
 *   field_types = {
 *     "text_with_summary"
 *   },
 *   quickedit = {
 *     "editor" = "form"
 *   }
 * )
 */
class TextSummaryFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#type' => 'processed_text',
        '#text' => NULL,
        '#format' => $item->format,
        '#langcode' => $item->getLangcode(),
      );

      if (!empty($item->summary)) {
        $elements[$delta]['#text'] = $item->summary;
      }
    }

    return $elements;
  }
}
