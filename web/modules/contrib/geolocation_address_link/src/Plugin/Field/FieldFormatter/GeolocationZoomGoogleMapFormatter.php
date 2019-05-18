<?php

namespace Drupal\geolocation_address_link\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\geolocation\Plugin\Field\FieldFormatter\GeolocationGoogleMapFormatter;

/**
 * Plugin implementation of the 'geolocation_zoom_map' formatter.
 *
 * Identical to the Google Maps formatter except it uses the specific zoom value
 * stored in the field, if any. The normal Geolocation field does not compute or store
 * a zoom value for individual elements. The zoom value can be added by the
 * AddressToGeo service when the entity is saved.
 *
 * @FieldFormatter(
 *   id = "geolocation_zoom_map",
 *   module = "geolocation_address_link",
 *   label = @Translation("Geolocation Google Maps API - Map with dynamic zoom"),
 *   field_types = {
 *     "geolocation"
 *   }
 * )
 */
class GeolocationZoomGoogleMapFormatter extends GeolocationGoogleMapFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = parent::viewElements($items, $langcode);
    if (empty($elements)) {
      return $elements;
    }

    // See if there is a zoom value stored in the field's data. If so, swap it into
    // the map settings.
    if (array_key_exists('#attached', $elements)) {
      $data = $items->get(0)->getValue()['data'];
      if (array_key_exists('zoom', $data)) {
        $unique_id = $elements['#uniqueid'];
        $elements['#attached']['drupalSettings']['geolocation']['maps'][$unique_id]['settings']['google_map_settings']['zoom'] = $data['zoom'];
      }
    }
    else {
      foreach ($elements as $delta => $element) {
        $data = $items->get($delta)->getValue()['data'];
        if (array_key_exists('zoom', $data)) {
          $unique_id = $element['#uniqueid'];
          $elements[$delta]['#attached']['drupalSettings']['geolocation']['maps'][$unique_id]['settings']['google_map_settings']['zoom'] = $data['zoom'];
        }
      }
    }
    return $elements;
  }

}
