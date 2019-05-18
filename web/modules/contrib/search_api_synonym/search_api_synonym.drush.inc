<?php

/**
 * @file
 * Drush commands for Search API Synonym.
 */

/**
 * Implements hook_drush_command().
 */
function search_api_synonym_drush_command() {
  $items = [];

  $items['search-api-synonym-export'] = [
    'description' => 'Export search synonyms to a specific format.',
    'examples' => [
      'drush search-api-synonym-export --plugin=solr langcode=da type=spelling_error filter=all' => dt('Export all Danish spelling errors in the Solr format.'),
      'drush sapi-syn-ex --plugin=solr langcode=da type=spelling_error filter=all' => dt('Export all Danish spelling errors in the Solr format.'),
    ],
    'options' => [
      'plugin' => dt('Machine name of the export plugin. E.g. solr.'),
      'langcode' => dt('Language being exported. Use the language code. E.g. en or da.'),
      'type' => dt('Synonym type. Allowed values: synonym = Synomyms, spelling_error = Spelling errors, all = All types (synonyms and spelling errors). Defaults to "alL".'),
      'filter' => dt('Export filter. Allowed values: nospace = Skip all words containing a space, onlyspace = Skip all words without a space. Defaults to "all".'),
      'incremental' => dt('Incremental export - use Unix timestamp. Only export synonyms changed after the provided timestamp.'),
      'file' => dt('File name used when saving the exported file. Include extension but not folder name!.'),
    ],
    'aliases' => ['sapi-syn-ex'],
  ];

  return $items;
}

/**
 * Export synonyms to a flat file.
 */
function drush_search_api_synonym_export() {
  // Plugin manager
  $pluginManager = \Drupal::service('plugin.manager.search_api_synonym.export');

  // Options
  $plugin = drush_get_option('plugin');
  $langcode = drush_get_option('langcode');
  $type = drush_get_option('type', 'all');
  $filter = drush_get_option('filter', 'all');
  $file = drush_get_option('file');
  $incremental = drush_get_option('incremental');

  // Validate option: plugin
  if (!$pluginManager->validatePlugin($plugin)) {
    $error = TRUE;
    drush_set_error(dt('--plugin is not valid. Please, use an existing plugin machine name.'));
  }

  // Validate option: langcode
  if (empty($langcode)) {
    $error = TRUE;
    drush_set_error(dt('--langcode is not valid. Please, use an existing language code.'));
  }

  // Validate option: type
  if (!empty($type) && !search_api_synonym_drush_validate_option_type($type)) {
    $error = TRUE;
    drush_set_error(dt('--type option is not valid. The only allowed values are "synonym", "spelling_error", "all".'));
  }

  // Validate option: filter
  if (!empty($filter) && !search_api_synonym_drush_validate_option_filter($filter)) {
    $error = TRUE;
    drush_set_error(dt('--filter option is not valid. The only allowed values are "nospace", "onlyspace", "all".'));
  }

  // Prepare export
  if (!isset($error)) {
    drush_log(dt('Starting synonym export....'), 'ok');

    $options = [
      'langcode' => $langcode,
      'type' => $type,
      'filter' => $filter,
      'file' => $file,
      'incremental' => (int) $incremental,
    ];
    $pluginManager->setPluginId($plugin);
    $pluginManager->setExportOptions($options);

    // Execute export
    if ($result = $pluginManager->executeExport()) {

      // Output result
      drush_log(dt('Synonyms export and saved in the file @file', ['@file' => $result]), 'ok');
    }
  }
}

/**
 * Validate that the type option is valid.
 *
 * @param string $type
 *   Type value from --type command option.
 *
 * @return boolean
 *   TRUE if valid, FALSE if invalid.
 */
function search_api_synonym_drush_validate_option_type($type) {
  $types = ['synonym', 'spelling_error', 'all'];
  return in_array($type, $types);
}

/**
 * Validate that the filter option is valid.
 *
 * @param string $filter
 *   Type value from --filter command option.
 *
 * @return boolean
 *   TRUE if valid, FALSE if invalid.
 */
function search_api_synonym_drush_validate_option_filter($filter) {
  $filters = ['nospace', 'onlyspace', 'all'];
  return in_array($filter, $filters);
}
