<?php

namespace Drupal\entityreference_view_mode\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_example_simple_text' formatter.
 *
 * @FieldFormatter(
 *   id = "entityreference_view_mode_field_formatter",
 *   module = "entityreference_view_mode",
 *   label = @Translation("Content View Formatter"),
 *   field_types = {
 *     "entityreference_view_mode_field_type"
 *   }
 * )
 */
class EntityReferenceViewModeFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $node = entity_load($item->target_type, $item->content);
      $elements[$delta] = entity_view($node, str_replace($item->target_type . '.', '', $item->view_mode));
    }

    return $elements;
  }

}
