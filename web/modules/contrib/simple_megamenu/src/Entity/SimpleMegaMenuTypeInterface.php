<?php

namespace Drupal\simple_megamenu\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Simple mega menu type entities.
 */
interface SimpleMegaMenuTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the target menu.
   *
   * @return array
   *   The menus targeted by the entity.
   */
  public function getTargetMenu();

  /**
   * Sets the target menus.
   *
   * @param array $target_menu
   *   The target menus.
   *
   * @return \Drupal\simple_megamenu\Entity\SimpleMegaMenuTypeInterface
   *   The SimpleMegaMenuType.
   */
  public function setTargetMenu($target_menu);

}
