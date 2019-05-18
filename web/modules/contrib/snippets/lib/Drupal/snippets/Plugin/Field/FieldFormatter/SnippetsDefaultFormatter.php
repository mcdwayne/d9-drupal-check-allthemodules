<?php

/**
 * @file
 * Contains \Drupal\snippets\Plugin\field\formatter\SnippetsDefaultFormatter.
 */

namespace Drupal\snippets\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'snippets_default' formatter.
 *
 * @FieldFormatter(
 *   id = "snippets_default",
 *   label = @Translation("Snippets default"),
 *   field_types = {
 *     "snippets_code"
 *   }
 * )
 */
class SnippetsDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    foreach ($items as $delta => $item) {
      $source = array(
        '#theme' => 'snippets_default',
        '#source_description' => check_plain($item->source_description),
        '#source_code' => check_plain($item->source_code),
      );
      $elements[$delta] = array('#markup' => drupal_render($source));
    }

    return $elements;
  }
}
