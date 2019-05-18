<?php

namespace Drupal\leaflet_countries\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\BasicStringFormatter;

/**
 * Plugin implementation of the 'leaflet_country' formatter.
 *
 * @FieldFormatter(
 *   id = "leaflet_country_raw",
 *   label = @Translation("Country code"),
 *   field_types = {
 *     "leaflet_country_item"
 *   }
 * )
 */
class LeafletCountryRaw extends BasicStringFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      // The text value has no text format assigned to it, so the user input
      // should equal the output, including newlines.
      $elements[$delta] = [
        '#type' => 'inline_template',
        '#template' => '{{ value }}',
        '#context' => ['value' => $item->value],
      ];
    }

    return $elements;
  }

}
