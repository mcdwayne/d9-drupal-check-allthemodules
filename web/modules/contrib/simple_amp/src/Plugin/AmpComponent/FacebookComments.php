<?php

namespace Drupal\simple_amp\Plugin\AmpComponent;

use Drupal\simple_amp\AmpComponentBase;

/**
 * FacebookComments AMP component.
 *
 * @AmpComponent(
 *   id = "amp-facebookcomments",
 *   name = @Translation("FacebookComments"),
 *   description = @Translation("Enables JS to display facebook comments"),
 *   regexp = {}
 * )
 */
class FacebookComments extends AmpComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    return '<script async custom-element="amp-facebook-comments" src="https://cdn.ampproject.org/v0/amp-facebook-comments-0.1.js"></script>';
  }

}
