<?php

namespace Drupal\geolocation_2gis\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'geolocation2gis_latlng' formatter.
 *
 * @FieldFormatter(
 *   id = "geolocation2gis_latlng",
 *   module = "geolocation_2gis",
 *   label = @Translation("Geolocation 2GIS Lat/Lng"),
 *   field_types = {
 *     "geolocation2gis"
 *   }
 * )
 */
class Geolocation2gisLatlngFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#theme' => 'geolocation_2gis_latlng_formatter',
        '#lat' => $item->lat,
        '#lng' => $item->lng,
      ];
    }

    return $element;
  }

}
