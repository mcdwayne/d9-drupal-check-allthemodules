<?php

/**
 * @file
 * Hooks provided by the CDEK API module.
 */

/**
 * Modify the list of pickup points.
 *
 * @param \CdekSDK\Common\Pvz[] $points
 *   Array of pickup points keyed by code.
 * @param array $params
 *   Parameters that were used to get the list of pickup points.
 *
 * @see \Drupal\cdek_api\Cdek::getPickupPoints()
 */
function hook_cdek_api_pickup_points_alter(array &$points, array $params) {
  foreach ($points as $code => $point) {
    // Limit the list to certain countries.
    if (!in_array($point->CountryCode, [1, 41, 42, 48])) {
      unset($points[$code]);
    }
  }
}
