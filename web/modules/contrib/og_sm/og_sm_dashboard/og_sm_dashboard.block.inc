<?php

/**
 * @file
 * Block callbacks.
 */

/**
 * Builds the render array for the site overview block.
 *
 * @return array
 *   A renderable array.
 */
function og_sm_dashboard_site_overview_block() {
  global $user;

  $content = array();
  $content['description']['#markup'] = t('All sites under your management.');

  $node_types = node_type_get_types();
  $site_types = og_sm_get_site_types();
  $items = array();
  foreach ($site_types as $type_machine_name) {
    if (!isset($node_types[$type_machine_name])) {
      continue;
    }
    if (!node_access('create', $type_machine_name, $user)) {
      continue;
    }
    $type = $node_types[$type_machine_name];
    $items[] = l(t('Add a new @site', array('@site' => $type->name)), 'node/add/' . $type_machine_name);
  }
  if ($items) {
    $content['node_add'] = array(
      '#theme' => 'item_list',
      '#items' => $items,
      '#attributes' => array('class' => 'site-actions'),
    );
    $content['clearfix'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('clearfix'),
      ),
    );
  }

  $rows = array();
  $og_sm_admin_exists = module_exists('og_sm_admin_menu');
  $og_sm_clone_exists = module_exists('og_sm_site_clone');
  foreach (og_sm_user_get_manageable_sites($user) as $site) {
    $row = array(
      'site_name' => l($site->title, 'node/' . $site->nid),
    );

    // Administer Site link.
    if ($og_sm_admin_exists && og_sm_site_permission_access($site, 'administer site')) {
      $row['admister'] = array(
        'data' => l(t('Administer'), 'group/node/' . $site->nid . '/admin'),
        'class' => 'site-action site-action-admin',
      );
    }
    elseif (node_access('update', $site, $user)) {
      $row['edit'] = array(
        'data' => l(t('Edit'), 'node/' . $site->nid . '/edit'),
        'class' => 'site-action site-action-edit',
      );
    }

    // Clone link.
    if ($og_sm_clone_exists && og_sm_site_clone_access_page($site, $user)) {
      $row['clone'] = array(
        'data' => l(t('Clone'), 'node/' . $site->nid . '/clone'),
        'class' => 'site-action site-action-clone',
      );
    }
    $rows[$site->nid] = $row;
  }

  $content['sites'] = array(
    '#theme' => 'table',
    '#rows' => $rows,
  );
  $content['#attached']['css'][] = drupal_get_path('module', 'og_sm_dashboard') . '/og_sm_dashboard.css';

  return $content;
}
