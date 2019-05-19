<?php

namespace Drupal\simple_amp\Plugin\AmpComponent;

use Drupal\simple_amp\AmpComponentBase;

/**
 * SocialShare AMP component.
 *
 * @AmpComponent(
 *   id = "amp-social-share",
 *   name = @Translation("SocialShare"),
 *   description = @Translation("Enables JS to display a social share button"),
 *   regexp = {}
 * )
 */
class SocialShare extends AmpComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    return '<script async custom-element="amp-social-share" src="https://cdn.ampproject.org/v0/amp-social-share-0.1.js"></script>';
  }

}
