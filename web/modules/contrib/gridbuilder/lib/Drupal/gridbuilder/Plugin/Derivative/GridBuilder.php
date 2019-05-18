<?php

/**
 * @file
 * Definition of Drupal\gridbuilder\Plugin\Derivative\GridBuilder.
 */

namespace Drupal\gridbuilder\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeInterface;

/**
 * GridBuilder plugin derivative definition.
 */
class GridBuilder implements DerivativeInterface {

  /**
   * List of derivatives.
   *
   * Associative array keyed by grid config key name. The values of the array are
   * associative arrays themselves with metadata about the grid.
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
    $grids = module_invoke('gridbuilder', 'load_all');
    if (!empty($grids)) {
      foreach ($grids as $key => $grid) {
        $this->derivatives[$key] = array(
          'grid' => $grid,
          'class' => 'Drupal\gridbuilder\Plugin\gridbuilder\gridbuilder\EqualColumnGrid',
        );
      }
    }
    return $this->derivatives;
  }

}
