<?php

namespace Drupal\tracking_number\Plugin\TrackingNumberType;

use Drupal\tracking_number\Plugin\TrackingNumberTypeBase;
use Drupal\Core\Url;

/**
 * Provides a DHL Global tracking number type.
 *
 * @TrackingNumberType(
 *   id = "dhl_global",
 *   label = @Translation("DHL Global")
 * )
 */
class DhlGlobal extends TrackingNumberTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTrackingUrl($number) {
    return Url::fromUri('http://webtrack.dhlglobalmail.com', [
      'query' => [
        'trackingnumber' => $number,
      ],
    ]);
  }

}
