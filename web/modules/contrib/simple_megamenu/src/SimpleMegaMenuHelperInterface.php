<?php

namespace Drupal\simple_megamenu;

use Drupal\simple_megamenu\Entity\SimpleMegaMenuTypeInterface;

/**
 * Interface SimpleMegaMenuHelperInterface.
 *
 * @package Drupal\simple_megamenu
 */
interface SimpleMegaMenuHelperInterface {

  /**
   * Gets the menus targeted by a specific Simple mega menu type.
   *
   * @param \Drupal\simple_megamenu\Entity\SimpleMegaMenuTypeInterface $entity
   *   The Simple mega menu type entity.
   *
   * @return array
   *   The menus targeted by the config entity.
   */
  public function getTargetMenus(SimpleMegaMenuTypeInterface $entity);

  /**
   * Get SimpleMegaMenuType entities which target a menu.
   *
   * @param string $menu_name
   *   The menu name.
   *
   * @return array
   *   An array keyed by the SimpleMegaMenuType id and with the label as value.
   *   Otherwise, an empty array.
   */
  public function getMegaMenuTypeWhichTargetMenu($menu_name);

  /**
   * Is the menu is referenced by a SimpleMegaMenuType entity.
   *
   * @param string $menu_name
   *   The menu name.
   *
   * @return bool
   *   TRUE, if the menu is targeted by a SimpleMegaMenuType entity.
   *   Otherwise, FALSE.
   */
  public function menuIsTargetedByMegaMenuType($menu_name);

  /**
   * Get a SimpleMegaMenuType entity.
   *
   * @param string $id
   *   The SimpleMegaMenuType id.
   *
   * @return \Drupal\simple_megamenu\Entity\SimpleMegaMenuTypeInterface $entity
   *   The SimpleMegaMenuTypeInterface entity.
   */
  public function getSimpleMegaMenuType($id);

  /**
   * Get a SimpleMegaMenu entity.
   *
   * @param string $id
   *   The SimpleMegaMenu id.
   *
   * @return \Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface $entity
   *   The SimpleMegaMenuInterface entity.
   */
  public function getSimpleMegaMenu($id);

}
