<?php

/**
 * @file
 * Contains \Drupal\toc_formatter\Plugin\field\formatter\TextDefaultFormatter.
 */

namespace Drupal\toc_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'toc_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "toc_formatter",
 *   label = @Translation("Table Of Contents"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   },
 *   edit = {
 *     "editor" = "direct"
 *   }
 * )
 */
class TextTocFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#theme' => 'toc_formatter_table_of_contents',
        '#content' => $item->processed,
        '#title' => t('Content'),
      );
    }

    return $elements;
  }

}
