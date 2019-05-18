<?php

/**
 * @file
 * Site Manager (og_sm) related hooks.
 */

use Drupal\og_sm\OgSm;

/**
 * Implements hook_og_sm_site_menu_links_discovered_alter().
 */
function og_sm_content_og_sm_site_menu_links_discovered_alter(array &$links) {
  $site_type_manager = OgSm::siteTypeManager();

  $content_add_link = [
    'route_name' => 'og_sm.site_content.add',
    'menu_name' => 'og_sm_admin_menu',
    'provider' => 'og_sm_content',
    'parent' => 'og_sm.site_content.add_page',
  ];

  foreach ($site_type_manager->getContentTypes() as $site_type) {
    $id = 'og.site_content.add.' . $site_type->id();
    $links[$id] = [
      'title' => $site_type->label(),
      'id' => $id,
      'route_parameters' => [
        'node_type' => $site_type->id(),
      ],
    ] + $content_add_link;
  }
}
