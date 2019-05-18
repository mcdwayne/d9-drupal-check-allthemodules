<?php

use \Drush\Log\LogLevel;

/**
 * Implements hook_drush_help().
 */
function drush_locale_sync_drush_help($section) {
  switch ($section) {
    case 'meta:locale:title':
      return dt('Interface translation');
    case 'meta:locale:summary':
      return dt('Interact with the interface translation system.');
    case 'drush:locale-export':
      return dt('Exports the Drupal interface translations as Gettext Portable Object (.po) files.');
    case 'drush:locale-import':
      return dt('Imports a Gettext Portable Object (.po) file.') . "\n\r" . dt('For Drupal 7, multiple translation groups can be skipped by defining variable locale_sync_skip_groups, the default values are: menu and taxonomy.');
    case 'drush:locale-sync':
      return dt('Export and import interface translations from one site into another site. Transfers via rsync.');
  }
}

/**
 * Implementation of hook_drush_command().
 */
function drush_locale_sync_drush_command() {
  $items = [];
  $items['locale-export'] = [
    'aliases' => ['locale-dump'],
    'description' => 'Exports the Drupal interface translations as Gettext Portable Object (.po) files.',
    'category' => 'locale',
    'examples' => [
      'drush locale-export' => 'Save all exported files in a date based directory that will be created under ~/drush-backups directory.',
    ],
    'options' => [
      'result-directory' => [
        'description' => 'Save to a directory. The directory should be writable by drush. If --result-directory is omitted then date based directory will be created under ~/drush-backups directory.',
        'example-value' => '/path/to/file',
        'value' => 'optional',
      ],
    ],
  ];

  $items['locale-import'] = [
    'description' => 'Imports a Gettext Portable Object (.po) file.',
    'category' => 'locale',
    'examples' => [
      'drush locale-import project.po nl default' => 'Import a Gettext Portable Object (.po) file into the dutch default group.',
    ],
    'arguments' => [
      'file' => [
        'description' => 'Gettext Portable Object (.po) file to import.',
        'example-value' => '/path/to/file.po',
      ],
      'langcode' => [
        'description' => 'The language code of the Gettext Portable Object (.po) file to import.',
        'example-value' => 'fr',
      ],
      'group' => [
        'description' => '(Drupal 7) The text group of the Gettext Portable Object (.po) file to import.',
        'example-value' => 'default',
      ],
    ],
    'required-arguments' => 2,
    'options' => [
      'import-mode' => [
        'description' => '(Drupal 7) Set the mode for importing. Setting this to 0 will overwrite all existing strings, 1 will keep existing strings. Omitting this value means that the default value of the importing site will be used. This is usually overwrite but with variable locale_sync_default_mode the default can be overridden.',
        'example-value' => '1',
        'value' => 'optional',
      ],
    ],
  ];

  $items['locale-sync'] = [
    'description' => 'Export and import interface translations from one site into another site. Transfers via rsync.',
    'category' => 'locale',
    'drush dependencies' => ['core'],
    'bootstrap' => DRUSH_BOOTSTRAP_NONE,
    'examples' => [
      'drush locale-sync @dev @prod' => 'Copy the translations defined in @dev to @prod.',
    ],
    'arguments' => [
      'from' => 'Name of subdirectory within /sites or a site-alias.',
      'to' => 'Name of subdirectory within /sites or a site-alias.',
    ],
    'required-arguments' => TRUE,
    'options' => [
      'import-mode' => [
        'description' => 'Set the mode for importing. Setting this to 0 will overwrite all existing strings, 1 will keep existing strings. Omitting this value means that the default value of the importing site will be used. This is usually overwrite but with variable locale_sync_default_mode the default can be overridden.',
        'example-value' => '1',
        'value' => 'optional',
      ],
    ],
  ];

  return $items;
}

/**
 * Command callback. Outputs all Drupal textgroups in all different languages.
 */
function drush_drush_locale_sync_locale_export() {
  $drupal_marjor_version = drush_drupal_major_version();
  if (!$drupal_marjor_version) {
    drush_log(dt('Drush Locale Sync was unable to locate Drupal\'s major version number.'), LogLevel::ERROR);
    return [];
  }

  $function = "_drush_locale_sync_locale_export_d{$drupal_marjor_version}";
  if (function_exists($function)) {
    return $function(_drush_locale_sync_prepare_result_dir());
  }

  drush_log(dt('Drupal @version is not supported by Drush Locale Sync.', ['@version' => $drupal_marjor_version]), LogLevel::ERROR);
  return [];
}

/**
 * Return the preferred directory for storing the exported language files.
 *
 * @return string
 *   Location of the export directory.
 */
function _drush_locale_sync_prepare_result_dir() {
  $result_directory = drush_get_option('result-directory', FALSE);

  if ($result_directory && !file_exists($result_directory)) {
    mkdir($result_directory);
  }

  // Determine result directory, if not set.
  if (empty($result_directory)) {
    // User did not pass a specific value for --result-file. Make one.
    $backup = drush_include_engine('version_control', 'backup');
    $result_directory = $backup->prepare_backup_dir('locale');
    if (empty($result_directory)) {
      $result_directory = drush_find_tmp();
    }
  }

  return $result_directory;
}

/**
 * Implements command callback 'drush_locale_sync_export' for Drupal 8.
 *
 * @param string $result_directory
 *   Export location of translation files.
 * @return array
 *   List of exported files.
 *
 * @see \Drupal\locale\Form\ExportForm::submitForm
 * @see drush_locale_sync_export()
 */
function _drush_locale_sync_locale_export_d8($result_directory) {
  $languages = \Drupal::languageManager()->getLanguages();
  $files = [];

  if (empty($languages)) {
    return $files;
  }

  $content_options = [
    'customized' => TRUE,
    'not_customized' => TRUE,
    'not_translated' => FALSE,
  ];

  $date = gmdate('Ymd_his');

  foreach ($languages as $language) {
    // Only dump translatable languages.
    if (!locale_is_translatable($language->getId())) {
      continue;
    }

    $reader = new \Drupal\locale\PoDatabaseReader();
    $reader->setLangcode($language->getId());
    $reader->setOptions($content_options);

    $language_name = $language->getName();
    $filename = drush_get_context('DRUSH_DRUPAL_SITE') . '-' . $language->getId() . '-' . $date . '.po';
    $filepath = $result_directory . '/' . $filename;
    $files[$language->getId()]['default'] = [
      'filename' => $filename,
      'filepath' => $filepath,
    ];

    $item = $reader->readItem();
    if (!empty($item)) {
      $header = $reader->getHeader();
      $header->setProjectName(\Drupal::config('system.site')->get('name'));
      $header->setLanguageName($language_name);

      $writer = new \Drupal\Component\Gettext\PoStreamWriter();
      $writer->setUri($result_directory . '/' . $filename);
      $writer->setHeader($header);

      $writer->open();
      $writer->writeItem($item);
      $writer->writeItems($reader);
      $writer->close();

      drush_log(dt("Exported .po file @file for @language.", [
        '@file' => $filepath,
        '@language' => $language_name,
      ]), LogLevel::OK);
    }
  }

  return $files;
}

/**
 * Implements command callback 'drush_locale_sync_locale_export' for Drupal 7.
 *
 * @param string $result_directory
 *   Export location of translation files.
 * @return array
 *   List of exported files.
 *
 * @see drush_locale_sync_locale_export()
 */
function _drush_locale_sync_locale_export_d7($result_directory) {
  $languages = language_list();

  $names = locale_language_list('name');
  unset($names['en']);

  $groups = module_invoke_all('locale', 'groups');
  $date = gmdate('Ymd_his');

  $files = [];

  // Dump each language + group in a file.
  foreach ($names as $langcode => $language_name) {
    foreach ($groups as $group => $group_name) {
      if (_drush_locale_sync_is_skippable_text_group($group)) {
        continue;
      }
      // $result_directory is passed in to us usually via --result-directory.  If the user
      // has set $options['result-directory'] = TRUE, then we
      // will generate PO dump files in the same backup
      // directory that pm-updatecode uses.

      $strings = _locale_export_get_strings($languages[$langcode], $group);
      $output = _locale_export_po_generate($languages[$langcode], $strings);

      $filename = drush_get_context('DRUSH_DRUPAL_SITE') . '-' . $langcode . '-' . $date . '-' . $group . '.po';
      $filepath = $result_directory . '/' . $filename;
      file_put_contents($filepath, $output);

      $files[$langcode][$group] = [
        'filename' => $filename,
        'filepath' => $filepath,
        'group_name' => $group_name,
      ];

      drush_log(dt("Exported .po file @file for @language - @group.", [
        '@file' => $filepath,
        '@language' => $language_name,
        '@group' => $group_name,
      ]), LogLevel::OK);
    }
  }
  return $files;
}

/**
 * Command callback. Imports a Gettext Portable Object (.po) file into a Drupal
 * text group for a set language.
 *
 * @param string $filename
 *   File to import.
 * @param string $langcode
 *   Language code to import.
 * @param string $group
 *   Group name to import.
 */
function drush_drush_locale_sync_locale_import($filename, $langcode, $group = 'default') {
  $drupal_marjor_version = drush_drupal_major_version();

  if (!$drupal_marjor_version) {
    return drush_log(dt('Drush Locale Sync was unable to locate Drupal\'s major version number.'), LogLevel::ERROR);
  }

  $function = "_drush_locale_sync_locale_import_d{$drupal_marjor_version}";
  if (function_exists($function)) {
    return $function($filename, $langcode, $group);
  }

  return drush_log(dt('Drupal @version is not supported by Drush Locale Sync.', ['@version' => $drupal_marjor_version]), LogLevel::ERROR);
}

/**
 * Implements command callback 'drush_locale_sync_locale_import' for Drupal 8.
 *
 * @param string $filename
 *   File to import.
 * @param string $langcode
 *   Language code to import.
 * @param string $group
 *   Group name to import.
 *
 * @see drush_locale_sync_locale_import()
 */
function _drush_locale_sync_locale_import_d8($filename, $langcode, $group) {
  \Drupal::moduleHandler()->loadInclude('locale', 'translation.inc');
  // Add language, if not yet supported.
  $language = \Drupal::languageManager()->getLanguage($langcode);

  $po_data = [
    '@file' => $filename,
    '@language' => $language->getName(),
  ];

  if (empty($language)) {
    $language = \Drupal\language\Entity\ConfigurableLanguage::createFromLangcode($langcode);
    $language->save();
    drush_log(dt('The language %language has been created.', ['%language' => dt($language->label())]), LogLevel::NOTICE);
  }

  $file = NULL;
  $input = @file_get_contents($filename);
  $input && $file = file_save_data($input, 'temporary://' . $langcode . '.po');

  if (!$input || !$file) {
    return drush_log(dt("Failed retrieving .po file @file for @language.", $po_data), LogLevel::ERROR);
  }

  \Drupal::moduleHandler()->loadInclude('locale', 'bulk.inc');

  $options = array_merge(_locale_translation_default_update_options(), [
    'langcode' => $langcode,
  ]);

  // Import file into Drupal.
  drush_log(dt("Importing .po file @file into @language.", $po_data), LogLevel::OK);

  $file = locale_translate_file_attach_properties($file, $options);
  $report = \Drupal\locale\Gettext::fileToDatabase($file, $options);

  if ($report['seek'] == filesize($file->uri)) {
    _locale_refresh_translations([$langcode]);
    locale_translate_batch_finished(TRUE, [
      'stats' => [
        $file->filename => $report,
      ],
      'files' => [$filename],
    ]);
  }
  else {
    drush_log(dt("Failed importing .po file @file into @language.", [
      '@file' => $filename,
      '@language' => $language->getName(),
    ]), LogLevel::ERROR);
  }

  file_unmanaged_delete($file->uri);
}

/**
 * Implements command callback 'drush_locale_sync_locale_import' for Drupal 7.
 *
 * @param string $filename
 *   File to import.
 * @param string $langcode
 *   Language code to import.
 * @param string $group
 *   Group name to import.
 *
 * @see drush_locale_sync_locale_import()
 */
function _drush_locale_sync_locale_import_d7($filename, $langcode, $group) {
  if (_drush_locale_sync_is_skippable_text_group($group)) {
    return drush_log(dt("Skipped importing .po file @file, see 'drush help locale-import' for more info.", ['@file' => $filename]), LogLevel::WARNING);
  }

  $default = variable_get('locale_sync_default_mode', LOCALE_IMPORT_OVERWRITE);
  $mode = drush_get_option('import-mode', $default);
  $mode = $mode == -1 ? $default : $mode;

  $languages = language_list('language');

  if (!isset($languages[$langcode])) {
    return drush_log(dt("Language @language does not exist in your Drupal setup.", ['@language' => $langcode]), LogLevel::ERROR);
  }

  $groups = module_invoke_all('locale', 'groups');
  $po_data = [
    '@file' => $filename,
    '@language' => $languages[$langcode]->name,
    '@group' => $groups[$group],
  ];

  $file = NULL;
  $input = file_get_contents($filename);
  $input && $file = file_save_data($input);

  if (!$input || !$file) {
    return drush_log(dt("Failed retrieving .po file @file for @language - @group.", $po_data), LogLevel::ERROR);
  }

  drush_log(dt("Importing .po file @file into @language - @group.", $po_data), LogLevel::OK);

  if (!_locale_import_po($file, $langcode, $mode, $group)) {
    drush_log(dt("Failed importing .po file @file into @language - @group.", $po_data), LogLevel::ERROR);
  }

  file_delete($file);
}

/**
 * Text groups that can be skipped in Drupal 7.
 *
 * Because these translations have vocabulary id's and term id's and
 * these are probably not the same on different environments.
 *
 * @param string $group
 *   Machine name of the text group.
 *
 * @return bool
 */
function _drush_locale_sync_is_skippable_text_group($group) {
  return in_array($group, variable_get('locale_sync_skip_groups', [
    'menu',
    'taxonomy',
  ]));
}

/**
 * Command callback. Sync Drupal locales.
 *
 * @param string $source
 *   Source location to export language files.
 * @param string $target
 *   Target location to import language files.
 */
function drush_drush_locale_sync_locale_sync($source, $target) {
  $mode = drush_get_option('import-mode', -1); // -1 == means let the importing site decide what the default is.
  $error = FALSE;

  // Preflight destination in case it defines the alias used by the source
  _drush_sitealias_get_record($target);

  // After preflight, get source and destination settings
  $source_settings = drush_sitealias_get_record($source);
  $source_db_url = drush_sitealias_get_db_spec($source_settings, FALSE, 'source-');
  $target_settings = drush_sitealias_get_record($target);
  $target_db_url = drush_sitealias_get_db_spec($target_settings, FALSE, 'source-');

  $txt_source = (isset($source_db_url['remote-host']) ? $source_db_url['remote-host'] . '/' : '') . $source_db_url['database'];
  $txt_target = (isset($target_db_url['remote-host']) ? $target_db_url['remote-host'] . '/' : '') . $target_db_url['database'];

  drush_print();
  if (drush_get_context('DRUSH_NOCOLOR')) {
    drush_print(dt("You will copy locale data from @source to @target.", [
      '@target' => $txt_target,
      '@source' => $txt_source,
    ]));
  }
  else {
    drush_print(dt("You will copy locale data from \033[1;32;40m\033[1m@source\033[0m to \033[1;32;40m\033[1m@target\033[0m.", [
      '@target' => $txt_target,
      '@source' => $txt_source,
    ]));
  }

  drush_print();

  if (!drush_confirm(dt('Do you really want to continue?'))) {
    return drush_user_abort();
  }

  drush_print();

  require_once DRUSH_BASE_PATH . '/commands/core/rsync.core.inc';

  $additional_options = [];

  // Get remote tmp directories. Extends *_settings, doesn't override them.
  $source_settings = drush_sitealias_evaluate_path($source . ':%temp', $additional_options, FALSE, "rsync", 'source-');
  $target_settings = drush_sitealias_evaluate_path($target . ':%temp', $additional_options, FALSE, "rsync", 'target-');

  $date = gmdate('Ymd_his');

  // Set remote dump directory.
  $source_dump_directory = $source_settings['path-aliases']['%temp'] . '/' . $date;

  // Set rsync remote dump directories.
  $source_path = $source_settings['evaluated-path'] . '/' . $date;
  $target_path = $target_settings['evaluated-path'];

  // Create dump.
  $result = drush_invoke_process($source, 'locale-export', $additional_options, ['result-directory' => $source_dump_directory], ['integrate' => TRUE]);

  drush_print();

  // Rsync dump.
  $options = _drush_build_rsync_options($additional_options);

  // Detect if both source and target are remote.
  $source_db_url = drush_sitealias_get_db_spec($source_settings, FALSE, 'source-');
  $target_db_url = drush_sitealias_get_db_spec($target_settings, FALSE, 'target-');
  if (isset($source_db_url['remote-host']) && isset($target_db_url['remote-host'])) {
    $temp_dir = drush_find_tmp() . '/';
    $error |= !drush_core_exec_rsync($source_path, $temp_dir, $options);
    $error |= !drush_core_exec_rsync($temp_dir . $date, $target_path, $options);
  }
  else {
    $error |= !drush_core_exec_rsync($source_path, $target_path, $options);
  }

  if ($error) {
    drush_print();
  }

  // Import dump.
  foreach ($result['object'] as $langcode => $groups) {
    foreach ($groups as $group_name => $file) {
      $result = drush_invoke_process($target, 'locale-import', [
        'file' => $target_settings['path'] . '/' . $date . '/' . $file['filename'],
        'language' => $langcode,
        'group' => $group_name,
      ], ['import-mode' => $mode]);

      $error |= $result['error_status'] == 1;
    }
  }

  drush_print();

  if ($error) {
    return drush_log(dt("Failed syncing the sites locale."), LogLevel::ERROR);
  }

  return drush_log(dt("Finished syncing the sites locale."), LogLevel::SUCCESS);
}
