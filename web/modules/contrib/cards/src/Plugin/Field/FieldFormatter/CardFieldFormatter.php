<?php

namespace Drupal\cards\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entityreference_view_mode\Plugin\Field\FieldFormatter\EntityReferenceViewModeFieldFormatter;

/**
 * Plugin implementation of the 'field_example_simple_text' formatter.
 *
 * @FieldFormatter(
 *   id = "card_field_formatter",
 *   module = "cards",
 *   label = @Translation("Card View Formatter"),
 *   field_types = {
 *     "card_field_type"
 *   }
 * )
 */
class CardFieldFormatter extends EntityReferenceViewModeFieldFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];

    foreach ($items as $delta => $item) {
      $node = entity_load($item->target_type, $item->content);
      if ($node) {
        $node->card = $item;
        $elements[0][$delta] = entity_view($node, str_replace($item->target_type . '.', '', $item->view_mode));
      }
    }
    return $elements;
  }

}
