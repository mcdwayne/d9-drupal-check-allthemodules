<?php

namespace Drupal\simple_amp\Plugin\AmpComponent;

use Drupal\simple_amp\AmpComponentBase;

/**
 * Analytics AMP component.
 *
 * @AmpComponent(
 *   id = "amp-analytics",
 *   name = @Translation("Analytics"),
 *   description = @Translation("Enables JS to capture analytics data from an AMP document"),
 *   regexp = {}
 * )
 */
class Analytics extends AmpComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    return '<script async custom-element="amp-analytics" src="https://cdn.ampproject.org/v0/amp-analytics-0.1.js"></script>';
  }

}
