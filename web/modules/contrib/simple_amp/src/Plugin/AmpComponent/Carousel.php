<?php

namespace Drupal\simple_amp\Plugin\AmpComponent;

use Drupal\simple_amp\AmpComponentBase;

/**
 * Carousel AMP component.
 *
 * @AmpComponent(
 *   id = "amp-carousel",
 *   name = @Translation("Carousel"),
 *   description = @Translation("Enables JS to display a carousel"),
 *   regexp = {}
 * )
 */
class Carousel extends AmpComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    return '<script async custom-element="amp-carousel" src="https://cdn.ampproject.org/v0/amp-carousel-0.1.js"></script>';
  }

}
