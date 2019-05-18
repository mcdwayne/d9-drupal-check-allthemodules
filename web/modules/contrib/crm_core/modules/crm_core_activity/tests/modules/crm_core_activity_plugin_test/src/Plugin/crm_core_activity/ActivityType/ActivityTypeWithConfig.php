<?php

namespace Drupal\crm_core_activity_plugin_test\Plugin\crm_core_activity\ActivityType;

use Drupal\crm_core_activity\ActivityTypePluginBase;

/**
 * Provides generic plugin for activity type.
 *
 * @ActivityTypePlugin(
 *   id = "with_config",
 *   label = @Translation("Activity type with config"),
 *   description = @Translation("Activity type with config plugin.")
 * )
 */
class ActivityTypeWithConfig extends ActivityTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'configuration_variable' => 'foo',
    ];
  }

}
