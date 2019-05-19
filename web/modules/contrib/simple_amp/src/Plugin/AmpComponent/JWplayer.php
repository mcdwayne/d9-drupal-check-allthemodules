<?php

namespace Drupal\simple_amp\Plugin\AmpComponent;

use Drupal\simple_amp\AmpComponentBase;

/**
 * JWplayer AMP component.
 *
 * @AmpComponent(
 *   id = "amp-jwplayer",
 *   name = @Translation("JW Player"),
 *   description = @Translation("Enables JS to display a cloud-hosted JW Player"),
 *   regexp = {
 *     "/<amp\-jwplayer.*><\/amp\-jwplayer>/isU",
 *   }
 * )
 */
class JWplayer extends AmpComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    return '<script async custom-element="amp-jwplayer" src="https://cdn.ampproject.org/v0/amp-jwplayer-0.1.js"></script>';
  }

}
