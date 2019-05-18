<?php

/**
 * @file
 * Provides views data for gridstack.module.
 */

/**
 * Implements hook_views_plugins_style_alter().
 */
function gridstack_views_plugins_style_alter(array &$plugins) {
  $plugins['gridstack'] = [
    'id'             => 'gridstack',
    'label'          => 'GridStack',
    'description'    => t('Display the results in a GridStack.'),
    'class'          => 'Drupal\gridstack\Plugin\views\style\GridStackViews',
    'display_types'  => ['normal'],
    'help'           => t('Displays the Views results as a GridStack.'),
    'parent'         => 'parent',
    'plugin_type'    => 'style',
    'register_theme' => FALSE,
    'theme'          => 'gridstack',
    'short_title'    => 'GridStack',
    'title'          => 'GridStack',
    'provider'       => 'gridstack',
  ];
}
