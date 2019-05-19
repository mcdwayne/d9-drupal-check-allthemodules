<?php

namespace Drupal\themekey;

use Drupal\Component\Plugin\PluginManagerInterface;

trait PropertyManagerTrait {

  /**
   * @var
   */
  private $propertyManager;

  /**
   * Gets the ThemeKey Property manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The ThemeKey Property manager.
   */
  protected function getPropertyManager() {
    if (!$this->propertyManager) {
      $this->propertyManager = \Drupal::service('plugin.manager.themekey.property');
    }

    return $this->propertyManager;
  }

  /**
   * Sets the ThemeKey Property manager to use.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface
   *   The ThemeKey Property manager.
   *
   * @return $this
   */
  public function setPropertyManager(PluginManagerInterface $propertyManager) {
    $this->propertyManager = $propertyManager;

    return $this;
  }

}
