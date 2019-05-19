<?php

namespace Drupal\watchdog_event_extras\Plugin\WEE;

use Drupal\watchdog_event_extras\WEEBase;
use Drupal\Core\Site\Settings;

/**
 * Provides a 'test' wee.
 *
 * @WEE(
 *   id = "location_wee",
 *   title = @Translation("Location"),
 * )
 */
class LocationWee extends WEEBase {

  /**
   * {@inheritdoc}
   */
  public function attached(&$attached, $dblog) {
    if ($dblog->hostname != '127.0.0.1' && $dblog->hostname != '::1') {
      $attached['library'][] = 'watchdog_event_extras/wee.location';
      $attached['drupalSettings']['wee']['hostname'] = $dblog->hostname;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function markup($dblog) {
    if ($dblog->hostname != '127.0.0.1' && $dblog->hostname != '::1') {
      if (!Settings::get('google_maps_api_key')) {
        \Drupal::messenger()->addMessage('Google Maps API key not found.', \Drupal::messenger()::TYPE_WARNING);
      }
      return '<div id="event-location-map" class=""></div>';
    }
    else {
      return '<div id="event-location-map-localhost" class="">Localhost ' . $dblog->hostname . '</div>';
    }
  }

}
