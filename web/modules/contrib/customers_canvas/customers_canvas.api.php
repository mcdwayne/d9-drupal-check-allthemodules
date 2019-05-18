<?php

/**
 * @file
 * API functions for Customer's Canvas Drupal integration module.
 */

/**
 * Example hook_customers_canvas_js_alter().
 *
 * @param array $data
 *   Keyed array that includes two variables that are used to include the
 *   Customer's Canvas JS integration.
 */
function hook_customers_canvas_js_alter(array &$data) {
  // Include the iframe on all pages.
  $data['include'] = TRUE;

  // Replace the module library that gets loaded.
  $data['url'] = drupal_get_path('module', 'custom_module') . '/customers_canvas.js';

  // If including this for blocks (which is possible using this alter hook), you
  // need to find a way to get the following arguments:
  if (empty($data['args']['owner_id'])) {
    global $user;
    $owner = $user;
    $data['args']['owner_id'] = $owner->uid;
  }
  if (empty($data['args']['entity_id'])) {
    // If on a node page, use the existing node.
    $current_node = menu_get_object('node');
    if (!empty($current_node)) {
      $data['args']['entity_id'] = $current_node->nid;
    }
    else {
      // Else, give it a default node.
      $data['args']['entity_id'] = 1;
    }
  }
  if (empty($data['args']['entity_type'])) {
    // Give it a default entity type.
    $data['args']['entity_type'] = 'node';
  }
}

/**
 * Example hook_customers_canvas_builder_link_alter().
 *
 * @param string $path
 *   Path is the path that was generated for the link that points to the
 *   builder.
 * @param array $context
 *   The array has three contexts: entity, entity_type, and owner.
 */
function hook_customers_canvas_builder_link_alter(&$path, array $context) {
  $entity = $context['entity'];

  // Change all links to use the user who created the node.
  $owner_id = $entity->uid;
  $path = variable_get("customers_canvas_builder_url", 'canvas') .
    $owner_id . '/' .
    $entity->$context['entity_id_label'] . '/' .
    $context['entity_type'];
}

/**
 * Example hook_customers_canvas_product_config_delta_alter().
 *
 * Simply set the $delta to whatever value you want.
 *
 * @param int $delta
 *   The delta of the field value to use for JSON.
 * @param object $entity
 *   The full entity that we have as context.
 */
function hook_customers_canvas_product_config_delta_alter(&$delta, $entity) {
  $delta = 0;
}

/**
 * Example hook_customers_canvas_xss_tags_alter().
 *
 * You can add or remove tags using this alter hook.
 */
function hook_customers_canvas_xss_tags_alter($allowed_tags) {
  return ['div'] + $allowed_tags;
}
