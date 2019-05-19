<?php

/**
 * @file
 * Theme functions
 */

define('VUD_WIDGET_MESSAGE_ERROR', 0);
define('VUD_WIDGET_MESSAGE_DENIED', 1);

/**
 * Implementation of hook_theme().
 */
function vud_theme() {

  $module_handler = \Drupal::service('module_handler');
  $module_path = $module_handler->getModule('vud')->getPath();

  return [
    'vud_widget' => [
      'variables' => [
        'id' => NULL,
        'entity_id' => NULL,
        'entity_type_id' => NULL,
        'show_links' => FALSE,
        'show_up_as_link' => FALSE,
        'show_down_as_link' => FALSE,
        'show_reset' => FALSE,
        'link_up' => NULL,
        'link_down' => NULL,
        'link_reset' => NULL,
        'class_up' => TRUE,
        'class_down' => TRUE,
        'class_reset' => TRUE,
        'link_class_up' => NULL,
        'link_class_down' => NULL,
        'link_class_reset' => NULL,
        'reset_short_text' => NULL,
        'reset_long_text' => NULL,
        'up_points' => 0,
        'down_points' => 0,
        'up_percent' => 0,
        'down_percent' => 0,
        'percent_text' => NULL,
        'unsigned_points' => 0,
        'points' => 0,
        'vote_sum' => 0,
        'widget_template' => 'alternate',
        'template_path' => NULL,
        'base_path' => $module_path,
        'widget_name' => NULL,
        'widget_instance_id' => NULL,
        'vote_label' => 'votes',
      ],
    ],
  ];
}

/**
 * Implements hook_theme_suggestions_HOOK().
 */
function vud_theme_suggestions_vud_widget(array $variables) {
  $suggestions = [];
  $widget_name = $variables['widget_name'];
  $entity_type_id = $variables['entity_type_id'];

  $suggestions[] = 'vud_widget__' . $widget_name;
  $suggestions[] = 'vud_widget__' . $entity_type_id;
  $suggestions[] = 'vud_widget__' . $entity_type_id . '__' . $widget_name;

  return $suggestions;
}
