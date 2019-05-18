<?php

namespace Drupal\chatbot_api\Plugin\Discovery;

use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;

/**
 * Intent derivative discovery decorator.
 *
 * Make sure the intent name (derivative_id) is used to discover derivatives
 * instead of a - default - concatenation of base_plugin_id and derivative_id.
 */
class IntentDerivativeDiscoveryDecorator extends ContainerDerivativeDiscoveryDecorator {

  /**
   * {@inheritdoc}
   */
  protected function encodePluginId($base_plugin_id, $derivative_id) {

    // Use derivative_id (intent name) as plugin ID.
    if ($derivative_id) {
      return $derivative_id;
    }

    return parent::encodePluginId($base_plugin_id, $derivative_id);
  }

}
