<?php

namespace Drupal\tb_megamenu;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an megamenu config entity.
 */
interface MegaMenuConfigInterface extends ConfigEntityInterface {

  /**
   * Sets the menu property and the first part of the id is it is not set.
   *
   * @param string $menuName
   *   The menu machine name.
   */
  public function setMenu($menuName);

  /**
   * Sets the theme property and the second part of the id if it is not set.
   *
   * @param string $themeName
   *   The theme machine name.
   */
  public function setTheme($themeName);

  /**
   * Gets the json decoded block configuration.
   *
   * @return array|\stdClass
   *   A class with properties for the block configuration settings.
   */
  public function getBlockConfig();

  /**
   * Converts the block config  to json and sets the blockConfig property.
   *
   * @param array|\stdClass $blockConfig
   *   The block configuration array / stdClass.
   */
  public function setBlockConfig($blockConfig);

  /**
   * Gets the json decoded menu configuration.
   *
   * @return array
   *   A class with properties for the menu configuration settings.
   */
  public function getMenuConfig();

  /**
   * Converts the menu config properties to json and sets the menu property.
   *
   * @param array|\stdClass $menuConfig
   *   The menu configuration array / stdClass.
   */
  public function setMenuConfig($menuConfig);

  /**
   * Loads the configuration info for the specified menu and theme.
   *
   * @param string $menu
   *   The menu machine name.
   * @param string $theme
   *   The theme machine name.
   *
   * @return MegaMenuConfigInterface
   *   Returns the config object or NULL if not found.
   */
  public static function loadMenu($menu, $theme);

}
