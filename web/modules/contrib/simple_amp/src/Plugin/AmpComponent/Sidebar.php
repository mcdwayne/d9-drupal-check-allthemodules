<?php

namespace Drupal\simple_amp\Plugin\AmpComponent;

use Drupal\simple_amp\AmpComponentBase;

/**
 * Sidebar AMP component.
 *
 * @AmpComponent(
 *   id = "amp-sidebar",
 *   name = @Translation("Sidebar"),
 *   description = @Translation("Enables JS to display a sidebar"),
 *   regexp = {}
 * )
 */
class Sidebar extends AmpComponentBase {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    return '<script async custom-element="amp-sidebar" src="https://cdn.ampproject.org/v0/amp-sidebar-0.1.js"></script>';
  }

}
