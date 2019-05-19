<?php

namespace Drupal\tracking_number\Plugin\TrackingNumberType;

use Drupal\tracking_number\Plugin\TrackingNumberTypeBase;
use Drupal\Core\Url;

/**
 * Provides a United Parcel Service tracking number type.
 *
 * @TrackingNumberType(
 *   id = "ups",
 *   label = @Translation("UPS")
 * )
 */
class Ups extends TrackingNumberTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTrackingUrl($number) {
    return Url::fromUri('http://wwwapps.ups.com/WebTracking/track', [
      'query' => [
        'track' => 'yes',
        'trackNums' => $number,
      ],
    ]);
  }

}
