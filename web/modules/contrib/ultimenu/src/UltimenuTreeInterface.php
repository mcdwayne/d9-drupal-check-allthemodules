<?php

namespace Drupal\ultimenu;

/**
 * Interface for Ultimenu tools.
 */
interface UltimenuTreeInterface {

  /**
   * Returns a list of links based on the menu name.
   *
   * @param string $menu_name
   *   The menu name.
   *
   * @return array
   *   An array of the requested menu links.
   */
  public function loadMenuTree($menu_name);

  /**
   * Returns a list of submenu links based on the menu name.
   *
   * @param string $menu_name
   *   The menu name.
   * @param string $link_id
   *   The link ID.
   * @param string $title
   *   The link title.
   *
   * @return array
   *   An array of the requested submenu links.
   */
  public function loadSubMenuTree($menu_name, $link_id, $title = '');

}
