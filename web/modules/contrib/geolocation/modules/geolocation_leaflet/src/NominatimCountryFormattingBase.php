<?php

namespace Drupal\geolocation_leaflet;

use Drupal\geolocation\GeocoderCountryFormattingBase;
use Drupal\geolocation\GeocoderCountryFormattingInterface;

/**
 * Base class for nominatim geocoder country formatting plugins.
 */
class NominatimCountryFormattingBase extends GeocoderCountryFormattingBase implements GeocoderCountryFormattingInterface {

  /**
   * {@inheritdoc}
   */
  public function format(array $atomics) {
    $address_elements = parent::format($atomics);

    if (
      $atomics['houseNumber']
      && $atomics['road']
    ) {
      $address_elements['addressLine1'] = $atomics['houseNumber'] . ' ' . $atomics['road'];
    }
    elseif ($atomics['road']) {
      $address_elements['addressLine1'] = $atomics['road'];
    }

    if (
      $atomics['city']
      && $atomics['village']
      && $atomics['city'] !== $atomics['village']
    ) {
      $address_elements['addressLine2'] = $atomics['village'];
    }
    elseif (
      $atomics['suburb']
    ) {
      $address_elements['addressLine2'] = $atomics['suburb'];
    }

    if ($atomics['city']) {
      $address_elements['locality'] = $atomics['city'];
    }
    elseif (
      empty($atomics['city'])
      && $atomics['county']
    ) {
      $address_elements['locality'] = $atomics['county'];
    }

    if ($atomics['postcode']) {
      $address_elements['postalCode'] = $atomics['postcode'];
    }

    if ($atomics['countryCode']) {
      $address_elements['countryCode'] = $atomics['countryCode'];
    }

    return $address_elements;
  }

}
