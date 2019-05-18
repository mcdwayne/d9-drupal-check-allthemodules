<?php

/**
 * @file
 * API documentation file for micro_site module.
 */

/**
 * Alter the list of sites that may be referenced.
 *
 * Note that this hook does not fire for users with the 'administer site entities'
 * permission.
 *
 * @param \Drupal\Core\Entity\Query\QueryInterface $query
 *   An entity query prepared by DomainSelection::buildEntityQuery().
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The account of the user viewing the reference list.
 * @param array $context
 *   A keyed array passing two items:
 *   - field_type The access field group used for this selection. Groups are
 *      'editor' for assigning editorial permissions (as in Domain Access)
 *      'admin' for assigning administrative permissions for a specific domain.
 *      Most contributed modules will use 'editor'.
 *   - entity_type The type of entity (e.g. node, user) that requested the list
 *     (only from widget options button / select list, otherwise empty with the
 *     entity_reference_autocomplete widget).
 *   - bundle The entity subtype (e.g. 'article' or 'page'). (only from widget
 *     options button / select list, otherwise empty with the
 *     entity_reference_autocomplete widget).
 *
 * No return value. Modify the $query object via methods.
 */
function hook_site_references_alter($query, $account, $context) {
  // Remove the active site from non-admins when editing node.
  if ($context['field_type'] == 'editor' && !$account->hasPermission('edit own site entity')) {
    if ($active_site = \Drupal::service('micro_site.negotiator')->getActiveSite()) {
      $query->condition('id', $active_site, '<>');
    }
  }
}
