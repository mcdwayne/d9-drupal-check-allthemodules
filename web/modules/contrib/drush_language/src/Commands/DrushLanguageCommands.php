<?php

namespace Drupal\drush_language\Commands;

use Drupal\drush_language\Service\DrushLanguageCliService;
use Drush\Commands\DrushCommands;
use Drush\Utils\StringUtils;

/**
 * Implements the Drush language commands.
 */
class DrushLanguageCommands extends DrushCommands {

  /**
   * The interoperability cli service.
   *
   * @var \Drupal\drush_language\Service\DrushLanguageCliService
   */
  protected $cliService;

  /**
   * DrushLanguageCommands constructor.
   *
   * @param \Drupal\drush_language\Service\DrushLanguageCliService $cliService
   *   The CLI service which allows interoperability.
   */
  public function __construct(DrushLanguageCliService $cliService) {
    $this->cliService = $cliService;
  }

  /**
   * Add and import one or more new language definitions.
   *
   * @codingStandardsIgnoreStart
   * @command language:add
   *
   * @param array $langcodes
   *   A comma-delimited list of langcodes for which a definition will be added.
   *
   * @aliases langadd,language-add
   * @codingStandardsIgnoreEnd
   */
  public function add(array $langcodes) {
    $langcodes = StringUtils::csvToArray($langcodes);

    $this->cliService->add($this->io(), 'dt', $langcodes);
  }

  /**
   * Assign an enabled language as default.
   *
   * @codingStandardsIgnoreStart
   * @command language:default
   *
   * @param string $langcode
   *   The langcode of the language which will be set as the default language.
   *
   * @aliases langdef,language-default
   * @codingStandardsIgnoreEnd
   */
  public function languageDefault($langcode) {
    $this->cliService->languageDefault($this->io(), 'dt', $langcode);
  }

  // @codingStandardsIgnoreStart
  /**
   * Import a single .po file.
   *
   * @command language:import:translations
   *
   * @param array $poFiles
   *   Comma-separated list of paths .po files containing the translations.
   *
   * @option langcode
   *   Language code to be imported. If not given, extracted from file name.
   * @option replace-customized
   *   Replace existing customized translations. Defaults to true.
   * @option replace-not-customized
   *   Replace existing not-customized translations. Defaults to true.
   * @option set-customized
   *   Set all existing translations as being customized. Defaults to true.
   * @option autocreate-language
   *   Autocreate any imported language if it does not yet exist. Defaults to
   *   true.
   *
   * @usage drush langimp
   *   Import all custom translations from the directory defined in
   *   $settings['custom_translations_directory'].
   * @usage drush langimp --langcode=ru file.po
   *   Import single file with explicit langcode.
   * @usage drush langimp --langcode=eo --no-set-customized --no-replace-customized de.po foomodule.fr.po barmodule-8.x-2.2-rc1.es.po
   *   Import not-customized (e.g. module) translations, without replacing
   *   custom translations, with auto langcode (these are the recognized
   *   patterns)'.
   *
   * @aliases langimp,language-import,language-import-translations
   */
  public function importTranslations(
    array $poFiles,
    array $options = [
      'langcode' => NULL,
      'replace-customized' => TRUE,
      'replace-not-customized' => TRUE,
      'set-customized' => TRUE,
      'autocreate-language' => TRUE,
    ]
  ) {
    $poFiles = StringUtils::csvToArray($poFiles);

    $this->cliService->importTranslations($this->io(), 'dt', $poFiles, $options);
  }
  // @codingStandardsIgnoreEnd

  // @codingStandardsIgnoreStart
  /**
   * Export string of a language as one or more .po files.
   *
   * @command language:export:translations
   *
   * @option statuses
   *   The statuses to export, defaults to 'customized'. This can be a
   *   comma-separated list of 'customized', 'not-customized', 'not-translated',
   *   or (as abbreviation) 'all'.
   * @option langcodes
   *   The language codes to export, comma-separated. Defaults to all enabled
   *   languages.
   * @option file
   *   The target file pattern. You can use %langcode as placeholder. Defaults
   *   to "%language.po". If the path is relative and does not start with ".",
   *   $settings[\'custom_translations_directory\'] must be defined and the path
   *   is relative to that directory.
   * @option force
   *   Write file even if no translations. Defaults to true.
   *
   * @usage drush langexp
   *   Export all custom translations into the directory defined in
   *   $settings['custom_translations_directory'].
   * @usage drush langexp --langcodes=de --status=customized,not-customized --file=all-de.po
   *   Export all german translated strings
   * @usage drush langexp --status=untranslated --file=./todo-%langcode.po
   *   Export untranslated strings from all languages to current dir
   *
   * @aliases langexp,language-export,language-export-translations
   */
  public function exportTranslations($options = [
    'statuses' => ['customized'],
    'langcodes' => [],
    'file' => '%langcode.po',
    'force' => TRUE,
  ]) {
    try {
      $this->cliService->exportTranslations($this->io(), 'dt', $options);
    }
    catch (\Exception $exception) {
    }
  }
  // @codingStandardsIgnoreEnd

}
