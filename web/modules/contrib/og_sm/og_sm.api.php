<?php

/**
 * @file
 * API documentation about the og_sm module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Act on a Site node being viewed.
 *
 * Will only be triggered when the node_view hook is triggered for a node type
 * that is a Site type.
 *
 * The hook can be put in the yourmodule.module OR in the yourmodule.og_sm.inc
 * file. The recommended place is in the yourmodule.og_sm.inc file as it keeps
 * your .module file cleaner and makes the platform load less code by default.
 *
 * @param array &$build
 *   A renderable array representing the entity content. The module may add
 *   elements to $build prior to rendering. The structure of $build is a
 *   renderable array as expected by drupal_render().
 * @param \Drupal\node\NodeInterface $site
 *   The site node.
 * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
 *   The entity view display holding the display options configured for the
 *   entity components.
 * @param string $view_mode
 *   The view mode the entity is rendered in.
 *
 * @see hook_node_view()
 */
function hook_og_sm_site_view(array &$build, \Drupal\node\NodeInterface $site, \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display, $view_mode) {

}

/**
 * Alter the Site homepage route info.
 *
 * The \Drupal\og_sm\SiteManager::getSiteHomePage() method creates and returns
 * the url instance to the frontpage (homepage) of a Site. That homepage is by
 * default the Site node detail page (node/[site-nid]).
 *
 * Implementations can require that the homepage links to a different page (eg.
 * group/node/NID/dashboard).
 *
 * This alter function allows modules to alter the route name and parameters.
 *
 * @param \Drupal\node\NodeInterface $site
 *   The entity object.
 * @param string $route_name
 *   The route name.
 * @param array $route_parameters
 *   The route parameters.
 */
function hook_og_sm_site_homepage_alter(\Drupal\node\NodeInterface $site, &$route_name, array &$route_parameters) {
  $route_name = 'og_sm.site.dashboard';
}

/**
 * Alters all the site menu links discovered by the menu link plugin manager.
 *
 * The hook can be put in the yourmodule.module OR in the yourmodule.og_sm.inc
 * file. The recommended place is in the yourmodule.og_sm.inc file as it keeps
 * your .module file cleaner and makes the platform load less code by default.
 *
 * @param array $links
 *   The link definitions to be altered.
 */
function hook_og_sm_site_menu_links_discovered_alter(array &$links) {

}

/**
 * @} End of "addtogroup hooks".
 */
