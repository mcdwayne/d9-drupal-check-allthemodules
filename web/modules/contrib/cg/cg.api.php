<?php

/**
 * @file
 * Hooks for the Content Guide module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter javascript settings used to generate the content guide.
 *
 * @param array $settings_js
 *   Javascript settings for a field.
 * @param array $context
 *   Additional information usefull when altering the settings.
 */
function hook_cg_field_settings_alter(array &$settings_js, array $context) {
  if ('my_field' === $context['field_name']) {
    // Force the Content Guide to be attach as a tooltip to the first <h2> in
    // the field widgets output.
    $settings_js['attachSelector'] = 'h2:first-of-type';
    $settings_js['cg']['display_type'] = 'tooltip';
  }
}

/**
 * Alter javascript settings used to generate the content guide for a field.
 *
 * @param array $settings_js
 *   Javascript settings for a field.
 * @param array $context
 *   Additional information usefull when altering the settings.
 */
function hook_cg_field_my_field_settings_alter(array &$settings_js, array $context) {
  // Force the Content Guide to be attach as a tooltip to the first <h2> in
  // the field widgets output.
  $settings_js['attachSelector'] = 'h2:first-of-type';
  $settings_js['cg']['display_type'] = 'tooltip';
}

/**
 * Alter settings used by the controller to get the content guide document.
 *
 * @param array $settings
 *   Content guide settings.
 * @param array $context
 *   Additional information usefull when altering the settings.
 */
function hook_cg_controller_widget_settings_alter(array &$settings, array $context) {
  // Use different document for field "title".
  if ('title' === $context['field']) {
    $settings['document_path'] = drupal_get_path('module', 'mymodule') . '/guide/title.md';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
