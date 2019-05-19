<?php
/**
 * @file
 * Widget Block drush integration.
 */

/**
 * Implements hook_drush_command().
 */
function widget_block_drush_command() {
  return [
    'widget-block-refresh' => [
      'description' => 'Refresh the Widget Block markup.',
      'options' => [
        'id' => 'Unique identifier of the Widget.',
        'language' => 'Language code which should be refreshed.',
        'forced' => 'Force Widget Block markup to refresh even if already up to date.',
      ],
      'aliases' => ['wbr'],
      'examples' => [
        'drush widget-block-refresh' => 'Refresh all Widget Blocks for all languages if needed.',
        'drush widget-block-refresh --forced' => 'Refresh all Widget Blocks for all languages.',
        'drush widget-block-refresh --id=00000000000000000000000000000000' => 'Refresh only specified Widget Block for all languages if needed.',
        'drush widget-block-refresh --id="00000000000000000000000000000000,00000000000000000000000000000001"' => 'Refresh only specified Widget Blocks for all languages if needed.',
        'drush widget-block-refresh --id="00000000000000000000000000000000,00000000000000000000000000000001" --forced' => 'Refresh only specified Widget Blocks for all languages.',
        'drush widget-block-refresh --language=en' => 'Refresh all Widget Blocks for specified language if needed.',
        'drush widget-block-refresh --language="en,nl"' => 'Refresh all Widget Blocks for specified languages if needed.',
        'drush widget-block-refresh --language="en,nl" --forced' => 'Refresh all Widget Blocks for specified languages.',
        'drush widget-block-refresh --id=00000000000000000000000000000000 --language=en' => 'Refresh specifed Widget Block for specified language if needed.',
        'drush widget-block-refresh --id=00000000000000000000000000000000 --language=en --forced' => 'Refresh specified Widget Block for specified language.',
        'drush widget-block-refresh --id="00000000000000000000000000000000,00000000000000000000000000000001" --language="en,nl"' => 'Refresh specified Widget Blocks for specified languages if needed.',
        'drush widget-block-refresh --id="00000000000000000000000000000000,00000000000000000000000000000001" --language="en,nl" --forced' => 'Refresh specified Widget Blocks for specified languages.',
      ],
    ],
    'widget-block-invalidate' => [
      'description' => 'Invalidate the Widget Block markup.',
      'options' => [
        'id' => 'Unique identifier of the Widget.',
        'language' => 'Language code which should be refreshed.',
      ],
      'aliases' => ['wbi'],
      'examples' => [
        'drush widget-block-invalidate' => 'Invalidate all Widget Blocks for all languages.',
        'drush widget-block-invalidate --id=00000000000000000000000000000000' => 'Invalidate specified Widget Block for all languages.',
        'drush widget-block-invalidate --id="00000000000000000000000000000000,00000000000000000000000000000001"' => 'Invalidate specified Widget Blocks for all languages.',
        'drush widget-block-invalidate --language=en' => 'Invalidate all Widget Blocks for specified language.',
        'drush widget-block-invalidate --language="en,nl"' => 'Invalidate all Widget Blocks for specified languages.',
        'drush widget-block-invalidate --id=00000000000000000000000000000000 --language=en' => 'Invalidate specified Widget Block for specified language.',
        'drush widget-block-invalidate --id="00000000000000000000000000000000,00000000000000000000000000000001" --language="en,nl"' => 'Invalidate specified Widget Blocks for specified languages.',
      ],
    ],
  ];
}

/**
 * Get the Widget Block configuration from the ID option.
 *
 * @return \Drupal\widget_block\Entity\WidgetBlockConfigInterface[]
 *   An array of WidgetBlockConfigInterface objects.
 */
function _drush_widget_block_get_id_option() {
  // Get a list of Widget Block configuration identifiers that need
  // to be refreshed.
  $id_option = drush_get_option('id');
  // Extract a list of identifiers from the options.
  $ids = $id_option === NULL ? NULL : explode(',', $id_option);
  // Load the Widget Block configuration for given identifiers.
  return \Drupal::entityTypeManager()->getStorage('widget_block_config')->loadMultiple($ids);
}

/**
 * Get a list of languages from the language option.
 *
 * @return \Drupal\Core\Language\LanguageInterface[]
 *   An array of LanguageInterface objects.
 */
function _drush_widget_block_get_language_option() {
  // Get a list of language codes that need to be used during refresh.
  $language_option = drush_get_option('language');
  // Get a list of available languages.
  $languages = \Drupal::languageManager()->getLanguages();

  // Check whether filtering should be performed.
  if ($language_option !== NULL) {
    // Extract the language codes from the option.
    $langcodes = explode(',', $language_option);
    // Filter the languages with only the languages specified in the options.
    $languages = array_filter($languages, function ($language) use ($langcodes) {
      return in_array($language->getId(), $langcodes);
    });
  }

  return $languages;
}

/**
 * Drush Command: Refresh Widget Blocks.
 */
function drush_widget_block_refresh() {
  // Determine whether the forced flag is set.
  $forced = drush_get_option('forced') !== NULL;
  // Get the Widget Block configurations.
  $configs = _drush_widget_block_get_id_option();
  // Get the languages.
  $languages = _drush_widget_block_get_language_option();

  // Set $count to zero.
  $count = 0;
  // Get the Widget Block backend service.
  $backend = \Drupal::service('widget_block.backend');
  // Iterate through the Widget Block configurations.
  foreach ($configs as $config) {
    // Iterate through the languages.
    foreach ($languages as $language) {
      // Perform Widget Block markup refresh.
      if ($backend->refresh($config, $language, $forced)) {
        // Increment the refresh counter.
        $count++;
      }
    }
  }

  // Build the message arguments.
  $args = ['count' => $count, 'total' => (count($configs) * count($languages))];
  // Notify about amount of refreshed widget blocks.
  drush_log("Widget Blocks refreshed: {$args['count']} of {$args['total']}", 'ok');
}

/**
 * Drush Command: Invalidate Widget Blocks.
 */
function drush_widget_block_invalidate() {
  // Get the Widget Block configurations.
  $configs = _drush_widget_block_get_id_option();
  // Get the languages.
  $languages = _drush_widget_block_get_language_option();

  // Set $count to zero.
  $count = 0;
  // Get the Widget Block backend service.
  $backend = \Drupal::service('widget_block.backend');
  // Iterate through the Widget Block configurations.
  foreach ($configs as $config) {
    // Iterate through the languages.
    foreach ($languages as $language) {
      // Perform Widget Block markup invalidation.
      if ($backend->invalidate($config, $language)) {
        // Increment the invalidation counter.
        $count++;
      }
    }
  }

  // Build the message arguments.
  $args = ['count' => $count, 'total' => (count($configs) * count($languages))];
  // Notify about amount of invalidate widget blocks.
  drush_log("Widget Blocks invalidated: {$args['count']} of {$args['total']}", 'ok');
}
