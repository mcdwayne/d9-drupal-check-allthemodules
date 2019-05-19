<?php

namespace Drupal\tracking_number\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for tracking number type plugins.
 */
abstract class TrackingNumberTypeBase extends PluginBase implements TrackingNumberTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    // Retrieve the @label property from the annotation and return it.
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getTrackingUrl($number);

}
