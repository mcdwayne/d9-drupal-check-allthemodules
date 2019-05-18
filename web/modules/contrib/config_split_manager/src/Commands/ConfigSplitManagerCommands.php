<?php

namespace Drupal\config_split_manager\Commands;

use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class ConfigSplitManagerCommands extends DrushCommands {
  /**
   * Export all configurations in each split directory
   *
   * @option option-name
   *   Description
   * @usage config-split-manager-export csmex
   *   Usage description
   *
   * @command config-split-manager:export
   * @aliases csmex
   */
  public function export() {
    try {
      // export configurations to sync directory
      \Drupal::service('config_split.cli')->ioExport(NULL, $this->io(), 'dt');
     // load config split entities
     $config_split_entities = \Drupal::entityTypeManager()->getStorage('config_split')->loadMultiple();

     foreach ($config_split_entities as $key => $config_split_entity) {
       try {
          // export configurations to each split directory
          \Drupal::service('config_split.cli')->ioExport($key, $this->io(), 'dt');
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
}
