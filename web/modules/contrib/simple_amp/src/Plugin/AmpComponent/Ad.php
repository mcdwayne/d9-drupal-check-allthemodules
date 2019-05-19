<?php

namespace Drupal\simple_amp\Plugin\AmpComponent;

use Drupal\simple_amp\AmpComponentBase;

/**
 * Advertisement AMP component.
 *
 * @AmpComponent(
 *   id = "amp-ad",
 *   name = @Translation("Advertisement"),
 *   description = @Translation("Enables JS to display ads"),
 *   regexp = {}
 * )
 */
class Ad extends AmpComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    return '<script async custom-element="amp-ad" src="https://cdn.ampproject.org/v0/amp-ad-0.1.js"></script>';
  }

}
