<?php

/**
 * @file
 * Drush commands for the D8 Rules Essentials module.
 */

/**
 * Implements hook_drush_help().
 */
function tr_rulez_drush_help($section) {
  switch ($section) {
    case 'meta:tr_rulez:title':
      return dt('Rules commands');

    case 'meta:tr_rulez:summary':
      return dt('Developer-specific commands for the Rules module.');
  }
}

/**
 * Implements hook_drush_command().
 */
function tr_rulez_drush_command() {
  $items['entity-list'] = [
    'aliases' => ['el'],
    'description' => dt('Show a list of available entities.'),
    'core' => ['8+'],
    'callback' => 'drush_tr_rulez_list_entities',
  ];
  $items['context-list'] = [
    'aliases' => ['cl'],
    'description' => dt('Show a list of available contexts.'),
    'core' => ['8+'],
    'callback' => 'drush_tr_rulez_list_contexts',
  ];
  $items['datatype-list'] = [
    'aliases' => ['tl'],
    'description' => dt('Show a list of available Typed Data datatypes.'),
    'core' => ['8+'],
    'callback' => 'drush_tr_rulez_list_datatypes',
  ];
  $items['datafilter-list'] = [
    'aliases' => ['fl'],
    'description' => dt('Show a list of available TypedDataFilter plugins.'),
    'core' => ['8+'],
    'callback' => 'drush_tr_rulez_list_datafilters',
  ];
  $items['formwidget-list'] = [
    'aliases' => ['wl'],
    'description' => dt('Show a list of available TypedDataFormWidget plugins.'),
    'core' => ['8+'],
    'callback' => 'drush_tr_rulez_list_formwidgets',
  ];
  $items['rules-events'] = [
    'description' => dt('Show a list of Rules events.'),
    'core' => ['8+'],
  ];
  $items['rules-conditions'] = [
    'description' => dt('Show a list of Rules conditions.'),
    'core' => ['8+'],
  ];
  $items['rules-actions'] = [
    'description' => dt('Show a list of Rules actions.'),
    'core' => ['8+'],
  ];
  $items['rules-expressions'] = [
    'description' => dt('Show a list of Rules expressions.'),
    'core' => ['8+'],
  ];

  return $items;
}

/**
 * Callback for the entity-list command.
 */
function drush_tr_rulez_list_entities() {
  $entities = array_keys(\Drupal::entityTypeManager()->getDefinitions());
  $unique = array_unique($entities);
  sort($unique);

  drush_print(dt("Entity machine names:"));
  drush_print("  " . implode(PHP_EOL . "  ", $unique) . PHP_EOL);
}

/**
 * Callback for the context-list command.
 */
function drush_tr_rulez_list_contexts() {
  $contexts = array_keys(\Drupal::service('context.repository')->getAvailableContexts());
  $unique = array_unique($contexts);
  sort($unique);

  drush_print(dt("Global context variables:"));
  drush_print("  " . implode(PHP_EOL . "  ", $unique) . PHP_EOL);
}

/**
 * Callback for the datatype-list command.
 */
function drush_tr_rulez_list_datatypes() {
  $definitions = \Drupal::service('typed_data_manager')->getDefinitions();
  $datatypes = [];
  foreach ($definitions as $plugin) {
//    $datatypes[] = $plugin['class'];
    $datatypes[] = $plugin['id'];
  }
  $unique = array_unique($datatypes);
  sort($unique);

  drush_print(dt("Available TypedData data types:"));
  drush_print("  " . implode(PHP_EOL . "  ", $unique) . PHP_EOL);
}

/**
 * Callback for the datafilter-list command.
 */
function drush_tr_rulez_list_datafilters() {
  tr_rulez_format_drush_command_output('plugin.manager.typed_data_filter', 'Available TypedDataFilter plugins:', FALSE);
}

/**
 * Callback for the formwidget-list command.
 */
function drush_tr_rulez_list_formwidgets() {
  tr_rulez_format_drush_command_output('plugin.manager.typed_data_form_widget', 'Available TypedDataFormWidget plugins:', FALSE);
}

/**
 * Implements drush_hook_COMMAND() for the rules-events command.
 */
function drush_tr_rulez_rules_events() {
  tr_rulez_format_drush_command_output('plugin.manager.rules_event', 'Available Rules Events:');
}

/**
 * Implements drush_hook_COMMAND() for the rules-conditions command.
 */
function drush_tr_rulez_rules_conditions() {
  tr_rulez_format_drush_command_output('plugin.manager.condition', 'Available Rules Conditions:');
}

/**
 * Implements drush_hook_COMMAND() for the rules-actions command.
 */
function drush_tr_rulez_rules_actions() {
  tr_rulez_format_drush_command_output('plugin.manager.rules_action', 'Available Rules Actions:');
}

/**
 * Implements drush_hook_COMMAND() for the rules-expressions command.
 */
function drush_tr_rulez_rules_expressions() {
  tr_rulez_format_drush_command_output('plugin.manager.rules_expression', 'Available Rules Expressions:');
}

/**
 * Helper function to format command output.
 */
function tr_rulez_format_drush_command_output($plugin_manager_service, $title, $categories = TRUE, $short = FALSE) {
  $definitions = \Drupal::service($plugin_manager_service)->getDefinitions();
  $plugins = [];
  foreach ($definitions as $plugin) {
    if ($categories) {
      if ($short) {
        $plugins[(string) $plugin['category']][] = $plugin['id'];
      }
      else {
        $plugins[(string) $plugin['category']][] = $plugin['label'] . '   (' . $plugin['id'] . ')';
      }
    }
    else {
      if ($short) {
        $plugins[] = $plugin['id'];
      }
      else {
        $plugins[] = $plugin['label'] . '   (' . $plugin['id'] . ')';
      }
    }
  }

  drush_print(dt($title));
  if ($categories) {
    ksort($plugins);
    foreach ($plugins as $category => $plugin_list) {
      drush_print("  " . $category);
      sort($plugin_list);
      drush_print("    " . implode(PHP_EOL . "    ", $plugin_list));
      drush_print("");
    }
  }
  else {
    $unique = array_unique($plugins);
    sort($unique);
    drush_print("  " . implode(PHP_EOL . "  ", $unique) . PHP_EOL);
  }
}
