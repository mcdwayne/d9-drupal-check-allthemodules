<?php

namespace Drupal\tracking_number\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for tracking number type plugins.
 */
interface TrackingNumberTypeInterface extends PluginInspectionInterface {

  /**
   * Returns the human-readable label for this tracking number type.
   *
   * @return string
   *   The label for this tracking number type.
   */
  public function getLabel();

  /**
   * Returns the tracking URL for a given tracking number of this type.
   *
   * It is the responsibility of this method to transform a raw tracking number
   * into a URL where tracking information for the tracking number can be found.
   *
   * @param string $number
   *   The tracking number.  Necessary for building the tracking URL.
   *
   * @return \Drupal\Core\Url
   *   A URL where tracking information for the provided tracking number can
   *   be found.
   */
  public function getTrackingUrl($number);

}
