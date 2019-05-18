<?php

/**
 * @file
 * Sends events related to nodes to Mixpanel.
 */

use \Drupal\Core\Entity\EntityInterface;

/**
 * Track a node event.
 */
function _mixpanel_defaults_track_node_event($event, $node) {
  $values = array(
    'node-title' => $node->label(),
    'node-id' => $node->id(),
    'node-type' => $node->bundle(),
  );

  // @todo: how does Organic Groups do this now?
  if (!empty($node->og_groups)) {
    $values['group-id'] = array_pop(array_values($node->og_groups));
  }

  mixpanel_track("node-$event", $values);
}

/**
 * Implements hook_node_delete().
 */
function mixpanel_defaults_node_delete(EntityInterface $node) {
  _mixpanel_defaults_track_node_event('deleted', $node);
}

/**
 * Implements hook_node_insert().
 */
function mixpanel_defaults_node_insert(EntityInterface $node) {
  _mixpanel_defaults_track_node_event('created', $node);
}

/**
 * Implements hook_node_update().
 */
function mixpanel_defaults_node_update(EntityInterface $node) {
  _mixpanel_defaults_track_node_event('updated', $node);
}

/**
 * Submit function for the search form to let us track searches.
 */
function mixpanel_defaults_search_form_submit($form, $form_state) {
  $keys = NULL !== $form_state->getValues('search_block_form') ? $form_state->getValues('search_block_form') : $form_state->getValues('keys');
  mixpanel_track("search", array('search-string' => $keys));
}
