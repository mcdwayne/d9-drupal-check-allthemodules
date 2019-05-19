<?php

namespace Drupal\themekey;

use Drupal\Component\Plugin\PluginManagerInterface;

trait OperatorManagerTrait {

  /**
   * @var
   */
  private $operatorManager;

  /**
   * Gets the ThemeKey Operator manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The ThemeKey Operator manager.
   */
  protected function getOperatorManager() {
    if (!$this->operatorManager) {
      $this->operatorManager = \Drupal::service('plugin.manager.themekey.operator');
    }

    return $this->operatorManager;
  }

  /**
   * Sets the ThemeKey Property manager to use.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface
   *   The ThemeKey Property manager.
   *
   * @return $this
   */
  public function setOperatorManager(PluginManagerInterface $operatorManager) {
    $this->operatorManager = $operatorManager;

    return $this;
  }

}
