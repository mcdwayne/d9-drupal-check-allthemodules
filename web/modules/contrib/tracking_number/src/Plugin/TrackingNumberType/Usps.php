<?php

namespace Drupal\tracking_number\Plugin\TrackingNumberType;

use Drupal\tracking_number\Plugin\TrackingNumberTypeBase;
use Drupal\Core\Url;

/**
 * Provides a United States Postal Service tracking number type.
 *
 * @TrackingNumberType(
 *   id = "usps",
 *   label = @Translation("United States Postal Service")
 * )
 */
class Usps extends TrackingNumberTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTrackingUrl($number) {
    return Url::fromUri('https://tools.usps.com/go/TrackConfirmAction', [
      'query' => [
        'tLabels' => $number,
      ],
    ]);
  }

}
