<?php

namespace Drupal\simple_amp\Plugin\AmpComponent;

use Drupal\simple_amp\AmpComponentBase;

/**
 * FacebookLike AMP component.
 *
 * @AmpComponent(
 *   id = "amp-facebooklike",
 *   name = @Translation("FacebookLike"),
 *   description = @Translation("Enables JS to display a facebook like button"),
 *   regexp = {}
 * )
 */
class FacebookLike extends AmpComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    return '<script async custom-element="amp-facebook-like" src="https://cdn.ampproject.org/v0/amp-facebook-like-0.1.js"></script>';
  }

}
