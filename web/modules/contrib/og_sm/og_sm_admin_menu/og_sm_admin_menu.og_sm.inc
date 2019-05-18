<?php

/**
 * @file
 * Site Manager (og_sm) related hooks.
 */

/**
 * Implements hook_og_sm_site_menu_links_discovered_alter().
 */
function og_sm_admin_menu_og_sm_site_menu_links_discovered_alter(array &$links) {

  if (\Drupal::moduleHandler()->moduleExists('views')) {
    $member_link = [
      'route_name' => 'entity.node.og_admin_routes.members',
      'menu_name' => 'og_sm_admin_menu',
      'provider' => 'og_sm_admin_menu',
    ];

    $links['og_sm.site.members'] = [
      'title' => t('Members'),
      'parent' => 'og_sm.site.admin',
      'id' => 'og_sm.site.members',
      'weight' => 40,
      'options' => [
        'attributes' => [
          'class' => ['toolbar-icon-entity-user-collection'],
        ],
      ],
    ] + $member_link;
    $links['og_sm.site.members.overview'] = [
      'title' => t('Member overview'),
      'description' => t('Administer site members'),
      'parent' => 'og_sm.site.members',
      'id' => 'og_sm.site.members.overview',
    ] + $member_link;
  }
}
