<?php

/**
 * @file
 * Provide views runtime hooks for entity_gallery.module.
 */

use Drupal\user\RoleInterface;
use Drupal\views\ViewExecutable;
use Drupal\user\Entity\Role;

/**
 * Implements hook_views_query_substitutions().
 */
function entity_gallery_views_query_substitutions(ViewExecutable $view) {
  $account = \Drupal::currentUser();
  return array(
    '***ADMINISTER_ENTITY_GALLERIES***' => intval($account->hasPermission('administer entity galleries')),
    '***VIEW_OWN_UNPUBLISHED_ENTITY_GALLERIES***' => intval($account->hasPermission('view own unpublished entity galleries')),
    '***BYPASS_ENTITY_GALLERY_ACCESS***' => intval($account->hasPermission('bypass entity gallery access')),
  );
}

/**
 * Implements hook_views_analyze().
 */
function entity_gallery_views_analyze(ViewExecutable $view) {
  $ret = array();
  // Check for something other than the default display:
  if ($view->storage->get('base_table') == 'entity_gallery') {
    foreach ($view->displayHandlers as $display) {
      if (!$display->isDefaulted('access') || !$display->isDefaulted('filters')) {
        // check for no access control
        $access = $display->getOption('access');
        if (empty($access['type']) || $access['type'] == 'none') {
          $anonymous_role = Role::load(RoleInterface::ANONYMOUS_ID);
          $anonymous_has_access = $anonymous_role && $anonymous_role->hasPermission('access entity galleries');
          $authenticated_role = Role::load(RoleInterface::AUTHENTICATED_ID);
          $authenticated_has_access = $authenticated_role && $authenticated_role->hasPermission('access entity galleries');
          if (!$anonymous_has_access || !$authenticated_has_access) {
            $ret[] = Analyzer::formatMessage(t('Some roles lack permission to access entity galleries, but display %display has no access control.', array('%display' => $display->display['display_title'])), 'warning');
          }
          $filters = $display->getOption('filters');
          foreach ($filters as $filter) {
            if ($filter['table'] == 'entity_gallery' && ($filter['field'] == 'status' || $filter['field'] == 'status_extra')) {
              continue 2;
            }
          }
          $ret[] = Analyzer::formatMessage(t('Display %display has no access control but does not contain a filter for published entity galleries.', array('%display' => $display->display['display_title'])), 'warning');
        }
      }
    }
  }
  foreach ($view->displayHandlers as $display) {
    if ($display->getPluginId() == 'page') {
      if ($display->getOption('path') == 'entity_gallery/%') {
        $ret[] = Analyzer::formatMessage(t('Display %display has set entity_gallery/% as path. This will not produce what you want. If you want to have multiple versions of the entity gallery view, use panels.', array('%display' => $display->display['display_title'])), 'warning');
      }
    }
  }

  return $ret;
}
