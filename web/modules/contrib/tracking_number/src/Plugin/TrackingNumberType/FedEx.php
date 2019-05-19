<?php

namespace Drupal\tracking_number\Plugin\TrackingNumberType;

use Drupal\tracking_number\Plugin\TrackingNumberTypeBase;
use Drupal\Core\Url;

/**
 * Provides a FedEx tracking number type.
 *
 * @TrackingNumberType(
 *   id = "fedex",
 *   label = @Translation("FedEx")
 * )
 */
class FedEx extends TrackingNumberTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTrackingUrl($number) {
    return Url::fromUri('http://www.fedex.com/Tracking', [
      'query' => [
        'action' => 'track',
        'tracknumbers' => $number,
      ],
    ]);
  }

}
