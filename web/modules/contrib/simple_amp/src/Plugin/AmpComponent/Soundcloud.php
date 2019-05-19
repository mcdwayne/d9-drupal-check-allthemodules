<?php

namespace Drupal\simple_amp\Plugin\AmpComponent;

use Drupal\simple_amp\AmpComponentBase;

/**
 * Soundcloud AMP component.
 *
 * @AmpComponent(
 *   id = "amp-soundcloud",
 *   name = @Translation("Soundcloud"),
 *   description = @Translation("Enables JS to display a Soundcloud clip"),
 *   regexp = {
 *     "/<amp\-soundcloud.*><\/amp\-soundcloud>/isU",
 *   }
 * )
 */
class Soundcloud extends AmpComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    return '<script async custom-element="amp-soundcloud" src="https://cdn.ampproject.org/v0/amp-soundcloud-0.1.js"></script>';
  }

}
