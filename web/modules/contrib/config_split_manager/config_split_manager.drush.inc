<?php
/**
* @file Contains the code to generate the custom drush commands.
*/

/**
* Implements hook_drush_command().
*/
function config_split_manager_drush_command() {
 $items = array();
 $items['config-all-splits-export'] = [
  'description' => 'Export all splits configuration into their directory.',
  'core' => ['8+'],
  'aliases' => ['csmex'],
  'options' => [],
  'drupal dependencies' => ['config_split_manager'],
  'examples' => [
    'drush config-split-export -y' => 'Export all active splits configurations.',
  ],
 ];
 return $items;
}

/**
* Call back function drush_config_split_manager_config_all_splits_export()
* to export all configurations in each split folder
* it is a fix of contrib module "config_split"
*/
function drush_config_split_manager_config_all_splits_export() {
  try {
    // Make the magic happen.
    \Drupal::service('config_split.cli')->ioExport(NULL, new ConfigSplitDrush8Io(), 'dt');
   // load config split entities
   $config_split_entities = \Drupal::entityTypeManager()->getStorage('config_split')->loadMultiple();

   foreach ($config_split_entities as $key => $config_split_entity) {
     try {
        // Make the magic happen.
        \Drupal::service('config_split.cli')->ioExport($key, new ConfigSplitDrush8Io(), 'dt');
      }
      catch (Exception $e) {
        return drush_set_error('DRUSH_CONFIG_ERROR', $e->getMessage());
      }
   }
  }
  catch (Exception $e) {
    return drush_set_error('DRUSH_CONFIG_ERROR', $e->getMessage());
  }
}
