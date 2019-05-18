<?php

/**
 * @file
 * Hooks specific to the Configuration Split Ignore module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the list of config entities that should be ignored.
 *
 * This should be used for config split specific configuration
 * which must be deleted when the split is disabled.
 * The configuration files must exist in the split directories.
 */
function hook_config_split_ignore_settings_alter(array &$settings) {
  $settings[] = 'mailchimp';
  $settings[] = 'webform.webform.*';
}

/**
 * @} End of "addtogroup hooks".
 */
