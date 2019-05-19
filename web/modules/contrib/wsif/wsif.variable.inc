<?php

/**
 * @file
 * Variable module integration.
 */

/**
 * Implements hook_variable_group_info().
 *
 * Create a variable group for each web service.
 */
function wsif_variable_group_info() {

  foreach (wsif_get_apis_info() as $api_name => $api_info) {
    $groups[$api_name] = array(
      'title' => $api_info['name'],
      'description' => $api_info['description'],
      'access' => 'administer site configuration',
    );
  }

  return $groups;
}

/**
 * Implements hook_variable_info().
 */
function wsif_variable_info($options) {
  $variable = array();

  foreach (wsif_get_apis_info() as $api_name => $api_info) {
    $variable["wsif-{$api_name}-unavailable"] = array(
      'title' => t('@api_name is unavailable', array('@api_name' => $api_info['name'])),
      'description' => t('Set to TRUE when the webservice is unavailable. This variable is managed by cron, never set it manually, use the -kill switch below instead.'),
      'type' => 'boolean',
      'default' => FALSE,
      'group' => $api_name,
    );

    $variable["wsif-{$api_name}-fail-window"] = array(
      'title' => t('@api_name fail window', array('@api_name' => $api_info['name'])),
      'description' => t('WSIF is semi-tolerant of failures, but if more than fail-cutoff failures occur within fail-window seconds then the service will be disabled.'),
      'type' => 'number',
      'default' => 5,
      'group' => $api_name,
    );

    $variable["wsif-{$api_name}-fail-cutoff"] = array(
      'title' => t('@api_name fail cutoff', array('@api_name' => $api_info['name'])),
      'description' => t('See fail-window above.'),
      'type' => 'number',
      'default' => 5,
      'group' => $api_name,
    );

    $variable["wsif-{$api_name}-fail-email"] = array(
      'title' => t('@api_name failure email', array('@api_name' => $api_info['name'])),
      'description' => t('The email address an email is sent to in the case of the webservice being cut off.'),
      'type' => 'mail_address',
      'default' => '',
      'group' => $api_name,
    );

    $variable["wsif-{$api_name}-kill"] = array(
      'title' => t('@api_name kill switch', array('@api_name' => $api_info['name'])),
      'description' => t('Set to TRUE to make the service unreachable, regardless of failure tracking.'),
      'type' => 'boolean',
      'default' => FALSE,
      'group' => $api_name,
    );
  }

  return $variable;
}
