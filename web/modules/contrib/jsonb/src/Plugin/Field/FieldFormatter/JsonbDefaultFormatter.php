<?php

/**
 * @file
 * Contains \Drupal\jsonb\Plugin\field\formatter\JsonbDefaultFormatter.
 */

namespace Drupal\jsonb\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'jsonb_default' formatter.
 *
 * @FieldFormatter(
 *   id = "jsonb_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "jsonb",
 *     "json",
 *   }
 * )
 */
class JsonbDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#type' => 'processed_text',
        '#text' => $item->value,
        '#format' => 'plain_text',
        '#langcode' => $item->getLangcode(),
      );
    }

    return $elements;
  }
}
