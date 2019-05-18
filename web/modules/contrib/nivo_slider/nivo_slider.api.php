<?php

/**
 * @file
 * Hooks provided by the Nivo Slider module.
 *
 * Modules and themes may implement any of the available hooks to interact with
 * the slider.
 */

/**
 * Register slider themes.
 *
 * This hook can be used to register themes for the slider. Themes will be
 * displayed and made selectable on the slider options administration page.
 *
 * Slider themes get a unique CSS class to use for styling and can specify an
 * unlimited number of CSS and JS files to include when the slider is
 * displayed.
 */
function hook_nivo_slider_theme_info() {
  return [
    'theme_name' => [
  // Human readable theme name.
      'name' => t('Theme name'),
  // Description of the theme.
      'description' => t('Theme description.'),
  // Theme supports thumbnail navigation.
      'thumb_support' => TRUE,
      'resources' => [
        'css' => [
  // Full path to a CSS file to include with the theme.
          drupal_get_path('module', 'module_name') . '/css/example.css',
          drupal_get_path('module', 'module_name') . '/css/demonstration.css',
        ],
        'js' => [
        // Full path to a JS file to include with the theme.
          drupal_get_path('module', 'module_name') . '/js/example.css',
          drupal_get_path('module', 'module_name') . '/js/demonstration.css',
        ],
      ],
    ],
  ];
}

/**
 * Alter slider themes.
 *
 * @param array $themes
 *   The associative array of theme information from
 *   hook_nivo_slider_theme_info().
 *
 * @see hook_nivo_slider_theme_info()
 */
function hook_nivo_slider_theme_info_alter(array &$themes) {
  // Modify the default theme's name and description.
  $themes['default']['name'] = t('My theme');
  $themes['default']['description'] = t('An excellent theme to appropriate for your own use!');

  // Disable thumbnail support.
  $themes['light']['thumb_support'] = FALSE;

  // Replace the default theme styling.
  $themes['dark']['resources']['css'] = drupal_get_path('module', 'my_module') . '/my_theme.css';
}
