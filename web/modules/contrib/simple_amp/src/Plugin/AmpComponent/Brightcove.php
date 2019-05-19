<?php

namespace Drupal\simple_amp\Plugin\AmpComponent;

use Drupal\simple_amp\AmpComponentBase;

/**
 * Brightcove AMP component.
 *
 * @AmpComponent(
 *   id = "amp-brightcove",
 *   name = @Translation("Brightcove"),
 *   description = @Translation("Enables JS to display the Brightcove Player"),
 *   regexp = {
 *     "/<amp\-brightcove.*><\/amp\-brightcove>/isU",
 *   }
 * )
 */
class Brightcove extends AmpComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    return '<script async custom-element="amp-brightcove" src="https://cdn.ampproject.org/v0/amp-brightcove-0.1.js"></script>';
  }

}
