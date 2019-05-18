<?php

/**
 * @file
 * Hooks provided by Production check module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Modify the list of available production check plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\prod_check\Plugin\ProdCheckPluginManager
 */
function hook_prod_check_info_alter(&$plugins) {
  if (isset($plugins['devel'])) {
    $plugins['devel']['title'] = t('My title');
  }
}

/**
 * Modify the list of available production check processor plugins.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\prod_check\Plugin\ProdCheckProcessorPluginManager
 */
function hook_prod_check_processor_info_alter(&$plugins) {
  if (isset($plugins['internal'])) {
    $plugins['internal']['title'] = t('My internal processor');
  }
}

/**
 * Modify the list of available production check processor categories.
 *
 * This hook may be used to modify plugin properties after they have been
 * specified by other modules.
 *
 * If you want to add a new one it is preferred that you create a yml file
 * names [module].prod_check_categories.yml
 *
 * @param array $plugins
 *   An array of all the existing plugin definitions, passed by reference.
 *
 * @see \Drupal\prod_check\Plugin\ProdCheckProcessorPluginManager
 */
function hook_prod_check_categories_info_alter(&$plugins) {
  if (isset($plugins['settings'])) {
    $plugins['settings']['title'] = t('My settings title');
  }
}

/**
 * @} End of "addtogroup hooks".
 */
