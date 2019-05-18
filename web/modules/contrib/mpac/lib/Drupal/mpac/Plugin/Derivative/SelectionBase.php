<?php

/**
 * @file
 * Contains \Drupal\mpac\Plugin\Derivative\SelectionBase.
 */

namespace Drupal\mpac\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Base class for selection plugins provided by Multi-path autocomplete.
 */
class SelectionBase implements DerivativeInterface {

  /**
   * Holds the list of plugin derivatives.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * Implements DerivativeInterface::getDerivativeDefinition().
   */
  public function getDerivativeDefinition($derivative_id, array $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * Implements DerivativeInterface::getDerivativeDefinitions().
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    $supported_types = array(
      'path',
      'shortcut',
    );
    foreach (mpac_selection_plugin_info() as $type => $info) {
      if (!in_array($type, $supported_types)) {
        $this->derivatives[$type] = $base_plugin_definition;
        $this->derivatives[$type]['label'] = t('@type selection', array('@type' => $info['label']));
      }
    }
    return $this->derivatives;
  }
}
