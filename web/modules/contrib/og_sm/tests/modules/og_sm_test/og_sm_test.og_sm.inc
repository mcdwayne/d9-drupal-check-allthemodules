<?php

/**
 * @file
 * Hooks implemented to test the OG SM functionality.
 */

use Drupal\node\NodeInterface;

/**
 * Implements hook_og_sm_site_homepage_alter().
 */
function og_sm_test_og_sm_site_homepage_alter(NodeInterface $site, &$route_name, array &$route_parameters) {
  $route_name = 'og_sm.site_edit';
  $route_parameters['entity_type_id'] = 'node';
}
