<?php

namespace Drupal\og_sm_menu;

use Drupal\node\NodeInterface;

/**
 * Interface for site menu manager classes.
 */
interface SiteMenuManagerInterface {

  const SITE_MENU_NAME = 'site_menu';

  /**
   * Gets the current site menu.
   *
   * @return \Drupal\og_menu\OgMenuInstanceInterface|null
   *   The og-menu instance, NULL if no menu was found in the current context.
   */
  public function getCurrentMenu();

  /**
   * Gets the menu that is linked to the passed site.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   *
   * @return \Drupal\og_menu\OgMenuInstanceInterface|null
   *   The og-menu instance, NULL if no menu was found for the passed site.
   */
  public function getMenuBySite(NodeInterface $site);

  /**
   * Creates a site menu for the passed site.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   *
   * @return \Drupal\og_menu\OgMenuInstanceInterface|null
   *   The og-menu instance, return NULL if the site already has a site menu.
   */
  public function createMenu(NodeInterface $site);

  /**
   * Gets all site menu instances.
   *
   * @return \Drupal\og_menu\OgMenuInstanceInterface[]
   *   An array of og-menu instances.
   */
  public function getAllMenus();

}
