<?php

/**
 * @file
 * API documentation for the iframe_resizer module.
 */

/**
 * Override iFrame Resizer host settings.
 *
 * Implements hook_iframe_resizer_host_settings_alter().
 *
 * @param array $settings
 *   An associative array of iFrame Resizer host settings. See the.
 *
 * @link https://github.com/davidjbradshaw/iframe-resizer iFrame Resizer
 *   documentation @endlink for the full list of supported parameters.
 */
function hook_iframe_resizer_host_settings_alter(&$settings) {
  // Alter the iFrame Resizer host settings.
  $settings['override_defaults'] = TRUE;
  $settings['options']['log'] = FALSE;
  $settings['options']['bodyBackground'] = 'green';
}

/**
 * Override iFrame Resizer hosted settings.
 *
 * Implements hook_iframe_resizer_hosted_settings_alter().
 *
 * @param array $settings
 *   An associative array of iFrame Resizer hosted settings. See the.
 *
 * @link https://github.com/davidjbradshaw/iframe-resizer iFrame Resizer
 *   documentation @endlink for the full list of supported parameters.
 */
function hook_iframe_resizer_hosted_settings_alter(&$settings) {
  // Alter the iFrame Resizer host settings.
  $settings['targetOrigin'] = 'https://google.com';
  $settings['heightCalculationMethod'] = 'max';
}
