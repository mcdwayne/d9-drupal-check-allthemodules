<?php

/**
 * @file
 * Drush (< 9) integration for the drush_language module.
 */

use Drupal\drush_language\Drush8Io;

/**
 * Implements hook_drush_command().
 *
 * @See drush_parse_command() for a list of recognized keys.
 */
function drush_language_drush_command() {
  $items = [];
  $items['language-add'] = [
    'description' => 'Add and import a new language definition',
    'arguments' => [
      'langcodes' => 'The langcodes of the languages for which a definition will be added.',
    ],
    'aliases' => ['langadd'],
  ];
  $items['language-default'] = [
    'description' => 'Assign an enabled language as default',
    'arguments' => [
      'langcode' => 'The langcode of the language which will be set as the default language.',
    ],
    'aliases' => ['langdef'],
  ];
  $items['language-import-translations'] = [
    'description' => 'Import a single .po file',
    'arguments' => [
      '.po file(s)' => 'Path to one or more .po files to import. If omitted, $settings[\'custom_translations_directory\'] must be set and all .po files from that directory will be taken. If langcode (overridable), project and version can be deduced from the filename, they will be stored in the translation database.',
    ],
    'examples' => [
      'Import all custom translations from the directory defined in $settings[\'custom_translations_directory\'].' => 'drush langimp',
      'Import single file with explicit langcode' => 'drush langimp --langcode=ru file.po',
      'Import not-customized (e.g. module) translations, without replacing custom translations, with auto langcode (these are the recognized patterns)' => 'drush langimp --langcode=eo --no-set-customized --no-replace-customized de.po foomodule.fr.po barmodule-8.x-2.2-rc1.es.po',
    ],
    'options' => [
      'langcode' => [
        'description' => 'Language code to be imported. If not given, extracted from file name.',
        'value' => 'optional',
      ],
      'replace-customized' => [
        'description' => 'Replace existing customized translations. Defaults to true.',
        'value' => 'optional',
      ],
      'replace-not-customized' => [
        'description' => 'Replace existing not-customized translations. Defaults to true.',
        'value' => 'optional',
      ],
      'set-customized' => [
        'description' => 'Set all existing translations as being customized. Defaults to true.',
        'value' => 'optional',
      ],
      'autocreate-language' => [
        'description' => 'Autocreate any imported language if it does not yet exist. Defaults to true.',
        'value' => 'optional',
      ],
    ],
    'aliases' => ['langimp', 'language-import'],
  ];
  $items['language-export-translations'] = [
    'description' => 'Export string of a language as one or more .po files',
    'examples' => [
      'Export all custom translations into the directory defined in $settings[\'custom_translations_directory\'].' => 'drush langexp',
      'Export all german translated strings' => 'drush langexp --langcodes=de --status=customized,not-customized --file=all-de.po',
      'Export untranslated strings from all languages to current dir' => 'drush langexp --status=untranslated --file=./todo-%langcode.po',
    ],
    'options' => [
      'statuses' => [
        'description' => "The statuses to export, defaults to 'customized'\nThis can be a comma-separated list of 'customized', 'not-customized', 'not-translated', or (as abbreviation) 'all'.",
        'value' => 'optional',
      ],
      'langcodes' => [
        'description' => 'The language codes to export, comma-separated. Defaults to all enabled languages.',
        'value' => 'optional',
      ],
      'file' => [
        'description' => 'The target file pattern. You can use %langcode as placeholder. Defaults to "%language.po". If the path is relative and does not start with ".", $settings[\'custom_translations_directory\'] must be defined and the path is relative to that directory.',
        'value' => 'optional',
      ],
      'force' => [
        'description' => 'Write file even if no translations. Defaults to true.',
        'value' => 'optional',
      ],
    ],
    'aliases' => ['langexp', 'language-export'],
  ];
  return $items;
}

/**
 * Add a language.
 */
function drush_drush_language_language_add() {
  $args = func_get_args();
  if (count($args) == 0) {
    return drush_set_error('DRUSH_LANGUAGE', dt('Please provide one or more language codes as arguments.'));
  }

  $langcodes = explode(',', $args[0]);
  \Drupal::service('drush_language.cli')->add(new Drush8Io(), 'dt', $langcodes);
}

/**
 * Assigns the default language.
 */
function drush_drush_language_language_default() {
  $args = func_get_args();
  if (count($args) == 0) {
    return drush_set_error('DRUSH_LANGUAGE', dt('Please provide one or more language codes as arguments.'));
  }

  foreach ($args as $langcode) {
    \Drupal::service('drush_language.cli')->languageDefault(new Drush8Io(), 'dt', $langcode);
  }
}

/**
 * Imports .po file(s).
 */
function drush_drush_language_language_import_translations() {
  // Get arguments and options.
  $file_paths = func_get_args();
  $options = [
    'langcode' => drush_get_option('langcode', NULL),
    'replace-customized' => drush_get_option('set-customized', TRUE),
    'replace-not-customized' => drush_get_option('replace-customized', TRUE),
    'set-customized' => drush_get_option('replace-not-customized', TRUE),
    'autocreate-language' => drush_get_option('autocreate-language', TRUE),
  ];

  \Drupal::service('drush_language.cli')->importTranslations(new Drush8Io(), 'dt', $file_paths, $options);
}

/**
 * Exports .po file(s).
 */
function drush_drush_language_language_export_translations() {
  // Get options.
  $options = [
    'statuses' => drush_get_option_list('statuses', 'customized'),
    'langcodes' => drush_get_option_list('langcodes'),
    'file' => drush_get_option('file', '%langcode.po'),
    'force' => drush_get_option('force', TRUE),
  ];

  \Drupal::service('drush_language.cli')->exportTranslations(new Drush8Io(), 'dt', $options);
}
