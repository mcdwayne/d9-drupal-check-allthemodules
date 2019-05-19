<?php

/**
 * @file
 * Contains \Drupal\toggle\Plugin\field\formatter\ToggleFormatter.
 */

namespace Drupal\toggle\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'list_toggle' formatter.
 *
 * @FieldFormatter(
 *   id = "list_toggle",
 *   label = @Translation("Toggle widget"),
 *   field_types = {
 *     "list_boolean",
 *     "list_integer",
 *     "list_float",
 *     "list_text"
 *   }
 * )
 */
class ToggleFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => field_filter_xss($item->value));
    }

    return $elements;
  }

}
