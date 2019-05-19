<?php

namespace Drupal\themekey;

use Drupal\Component\Plugin\PluginManagerInterface;

trait PropertyAdminManagerTrait {

  /**
   * @var
   */
  private $propertyAdminManager;

  /**
   * Gets the ThemeKey Property manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The ThemeKey Property Admin manager.
   */
  protected function getPropertyAdminManager() {
    if (!$this->propertyAdminManager) {
      $this->propertyAdminManager = \Drupal::service('plugin.manager.themekey.property_admin');
    }

    return $this->propertyAdminManager;
  }

  /**
   * Sets the ThemeKey Property manager to use.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface
   *   The ThemeKey Property manager.
   *
   * @return $this
   */
  public function setPropertyAdminManager(PluginManagerInterface $propertyAdminManager) {
    $this->propertyAdminManager = $propertyAdminManager;

    return $this;
  }

}
