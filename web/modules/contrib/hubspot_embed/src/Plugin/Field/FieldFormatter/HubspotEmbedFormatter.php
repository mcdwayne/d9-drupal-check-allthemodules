<?php

namespace Drupal\hubspot_embed\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_hubspot_embed_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_hubspot_embed_formatter",
 *   module = "hubspot_embed",
 *   label = @Translation("Display Hubspot Embed code"),
 *   field_types = {
 *     "string_long",
 *     "text_long"
 *   }
 * )
 */
class HubspotEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme'     => 'hubspot_embed',
        '#embed' => $item->value,
      ];
    }

    return $elements;
  }

}
