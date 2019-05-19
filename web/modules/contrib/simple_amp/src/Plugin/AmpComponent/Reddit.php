<?php

namespace Drupal\simple_amp\Plugin\AmpComponent;

use Drupal\simple_amp\AmpComponentBase;

/**
 * Reddit AMP component.
 *
 * @AmpComponent(
 *   id = "amp-reddit",
 *   name = @Translation("Reddit"),
 *   description = @Translation("Enables JS to display a Reddit comment or post embed"),
 *   regexp = {
 *     "/<amp\-reddit.*><\/amp\-reddit>/isU",
 *   }
 * )
 */
class Reddit extends AmpComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    return '<script async custom-element="amp-reddit" src="https://cdn.ampproject.org/v0/amp-reddit-0.1.js"></script>';
  }

}
