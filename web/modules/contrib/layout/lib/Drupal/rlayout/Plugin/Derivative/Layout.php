<?php

/**
 * @file
 * Definition of Drupal\rlayout\Plugin\Derivative\Layout.
 */

namespace Drupal\rlayout\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * Layout plugin derivative definition.
 */
class Layout implements DerivativeInterface {

  /**
   * List of derivatives.
   *
   * Associative array keyed by responsive layout machine name. The values of
   * the array are associative arrays themselves with metadata about the
   * layout such as the order of regions and breakpoint overrides.
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
    // Use module_invoke() because plugins are active even if the module is not
    // enabled.
    $this->derivatives = array();
    $layouts = module_invoke('rlayout', 'load_all');
    if (!empty($layouts)) {
      foreach ($layouts as $key => $layout) {
        $this->derivatives[$key] = array(
          'id' => $layout->id(),
          'title' => $layout->label(),
          'available regions' => $layout->regions,
          'region overrides' => $layout->overrides,
          'class' => 'Drupal\rlayout\Plugin\layout\layout\ResponsiveLayout',
        );
      }
    }
    return $this->derivatives;
  }
}
