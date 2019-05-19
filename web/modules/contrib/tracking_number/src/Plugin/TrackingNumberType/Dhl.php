<?php

namespace Drupal\tracking_number\Plugin\TrackingNumberType;

use Drupal\tracking_number\Plugin\TrackingNumberTypeBase;
use Drupal\Core\Url;

/**
 * Provides a DHL tracking number type.
 *
 * @TrackingNumberType(
 *   id = "dhl",
 *   label = @Translation("DHL")
 * )
 */
class Dhl extends TrackingNumberTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTrackingUrl($number) {
    return Url::fromUri('http://www.dhl.com/en/express/tracking.html', [
      'query' => [
        'AWB' => $number,
        'brand' => 'DHL',
      ],
    ]);
  }

}
