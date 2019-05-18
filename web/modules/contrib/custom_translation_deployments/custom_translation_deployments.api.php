<?php

/**
 * @file
 * Hooks for the Custom translation deployments module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide custom translation files information to be deployed.
 *
 * In this case file is custom-mycompany.<langcode>.po. You can return multiple
 * entries if you want to deploy multiple files.
 */
function hook_custom_translation_deployments_files() {
  $items = [];
  $items[] = [
    'name' => 'custom',
    'project_type' => 'module',
    'core' => '8.x',
    // We set the version to something static, but not to "dev".
    'version' => 'mycompany',
    'server_pattern' => 'http://ftp.drupal.org/files/translations/%core/%project/%project-%version.%language.po',
    'status' => 1,
  ];
  return $items;
}

/**
 * @} End of "addtogroup hooks".
 */
