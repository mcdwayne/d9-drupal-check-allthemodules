<?php

namespace Drupal\drush_language\Service;

use Drupal\Component\Gettext\PoStreamWriter;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Site\Settings;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\locale\PoDatabaseReader;
use Webmozart\PathUtil\Path;

/**
 * Class DrushLanguageCliService.
 *
 * @package Drupal\drush_language
 *
 * @internal This service is not an api and may change at any time.
 */
class DrushLanguageCliService {

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language_manager service.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The module_handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * List of messages.
   *
   * @var array
   */
  protected $errors;

  /**
   * DrushLanguageCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config.factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager service.
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $languageManager
   *   The language_manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module_handler service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager,
    ConfigurableLanguageManagerInterface $languageManager,
    ModuleHandlerInterface $moduleHandler
  ) {
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->moduleHandler = $moduleHandler;
    $this->errors = [];
  }

  /**
   * Add and import one or more new language definitions.
   *
   * @param \Symfony\Component\Console\Style\StyleInterface|\Drupal\drush_language\Drush8Io $io
   *   The $io interface of the cli tool calling.
   * @param callable $t
   *   The translation function akin to t().
   * @param array $langcodes
   *   A list of langcodes for which a definition will be added.
   */
  public function add($io, callable $t, array $langcodes) {
    if (empty($langcodes)) {
      $io->error($t('Please provide one or more comma-separated language codes as arguments.'));
      return;
    }

    foreach ($langcodes as $langcode) {
      $messageArgs = ['@langcode' => $langcode];
      // In the foreach loop because the list changes on successful iterations.
      $languages = $this->languageManager->getLanguages();

      // Do not re-add existing languages.
      if (isset($languages[$langcode])) {
        $io->warning($t('The language with code @langcode already exists.', $messageArgs));
        continue;
      }

      // Only allow adding languages for predefined langcodes.
      // In the foreach loop because the list changes on successful iterations.
      $predefined = $this->languageManager->getStandardLanguageListWithoutConfigured();
      if (!isset($predefined[$langcode])) {
        $io->warning($t('Invalid language code: @langcode', $messageArgs));
        continue;
      }

      // Add the language definition.
      $language = ConfigurableLanguage::createFromLangcode($langcode);
      $language->save();

      // Download and import translations for the newly added language if
      // interface translation is enabled.
      if ($this->moduleHandler->moduleExists('locale')) {
        module_load_include('fetch.inc', 'locale');
        $options = _locale_translation_default_update_options();
        if ($batch = locale_translation_batch_update_build([], [$langcode], $options)) {
          batch_set($batch);
          $batch =& batch_get();
          $batch['progressive'] = FALSE;

          // Process the batch.
          drush_backend_batch_process();
        }
      }

      $io->text($t('Added language: @langcode', $messageArgs));
    }
  }

  /**
   * Assign an enabled language as default.
   *
   * @param \Symfony\Component\Console\Style\StyleInterface|\Drupal\drush_language\Drush8Io $io
   *   The $io interface of the cli tool calling.
   * @param callable $t
   *   The translation function akin to t().
   * @param string $langcode
   *   The langcode of the language which will be set as the default language.
   */
  public function languageDefault($io, callable $t, $langcode) {
    $messageArgs = ['@langcode' => $langcode];
    $languages = $this->languageManager->getLanguages();
    if (!isset($languages[$langcode])) {
      $io->warning($t('Specified language does not exist: @langcode', $messageArgs));
      return;
    }

    $this->configFactory->getEditable('system.site')->set('default_langcode', $langcode)->save();
    $this->languageManager->reset();
  }

  /**
   * Import a single .po file.
   *
   * @param \Symfony\Component\Console\Style\StyleInterface|\Drupal\drush_language\Drush8Io $io
   *   The $io interface of the cli tool calling.
   * @param callable $t
   *   The translation function akin to t().
   * @param array $poFiles
   *   A list of paths .po files containing the translations.
   * @param array $options
   *   The command options.
   *
   * @see \Drupal\locale\Form\ImportForm::submitForm
   *
   * @todo Implement \Drupal\locale\Form\ImportForm::buildForm
   * @todo This can be simplified once https://www.drupal.org/node/2631584
   *       lands in Drupal core.
   */
  public function importTranslations(
    $io,
    callable $t,
    array $poFiles,
    array $options = [
      'langcode' => NULL,
      'replace-customized' => TRUE,
      'replace-not-customized' => TRUE,
      'set-customized' => TRUE,
      'autocreate-language' => TRUE,
    ]
  ) {
    $this->moduleHandler->loadInclude('locale', 'translation.inc');
    $this->moduleHandler->loadInclude('locale', 'bulk.inc');

    $opt_langcode = $options['langcode'];
    $opt_set_customized = $options['set-customized'];
    $opt_replace_customized = $options['replace-customized'];
    $opt_replace_not_customized = $options['replace-not-customized'];
    $opt_autocreate_language = $options['autocreate-language'];

    if (!$poFiles) {
      if ($dir = Settings::get('custom_translations_directory')) {
        $poFiles = glob(DRUPAL_ROOT . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . '*.po');
      }
      else {
        $io->success($t('Nothing to do, no file given and no custom translation directory set.'));
      }
    }

    $importer_options = array_merge(_locale_translation_default_update_options(), [
      'langcode' => $opt_langcode,
      'customized' => $opt_set_customized ? LOCALE_CUSTOMIZED : LOCALE_NOT_CUSTOMIZED,
      'overwrite_options' => [
        'customized' => (int) $opt_replace_customized,
        'not_customized' => (int) $opt_replace_not_customized,
      ],
    ]);

    // Import language files.
    $files = [];
    $langcodes_to_import = [];
    foreach ($poFiles as $file_path) {
      // Ensure we have the file intended for upload.
      if (file_exists($file_path)) {
        $file = locale_translate_file_create($file_path);

        // Extract project, version and language code from the file name
        // Supported:
        // - {project}-{version}.{langcode}.po, {prefix}.{langcode}.po
        // - {langcode}.po
        // Note: $options['langcode'] will override file langcode.
        $file = locale_translate_file_attach_properties($file, $importer_options);
        if ($file->langcode == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
          if (!$opt_langcode) {
            $io->error($t('Can not autodetect language of file @file', ['@file' => $file_path]));
            return;
          }
          $file->langcode = $opt_langcode;
          if (empty($file->version) && !empty($file->project) && !empty($file->langcode)) {
            $sources = locale_translation_get_status();
            $source = $sources[$file->project][$file->langcode];
            if (isset($source->version)) {
              $file->version = $source->version;
            }
          }
        }
        $langcodes_to_import[$file->langcode] = $file->langcode;
        $files[] = $file;
      }
      else {
        $io->error($t('File to import at @filepath not found.', ['@filepath' => $file_path]));
      }
    }

    if ($opt_autocreate_language) {
      $languages = $this->languageManager->getLanguages();
      foreach ($langcodes_to_import as $langcode_to_import) {
        if (!isset($languages[$langcode_to_import])) {
          try {
            /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $language_storage */
            $language_storage = $this->entityTypeManager->getStorage('configurable_language');
            $language = $language_storage->create(['langcode' => $opt_langcode]);
            $io->success($t('The language @id (@label) has been created.', [
              '@id' => $language->id(),
              '@label' => $language->label(),
            ]));
          }
          catch (InvalidPluginDefinitionException $exception) {
            $io->error($exception->getMessage());
          }
        }
      }
    }

    $batch = locale_translate_batch_build($files, $importer_options);
    batch_set($batch);

    // Create or update all configuration translations for this language.
    if ($batch = locale_config_batch_update_components($importer_options, $langcodes_to_import)) {
      batch_set($batch);
    }

    drush_backend_batch_process();
    $io->success($t('Import complete.'));
  }

  /**
   * Export strings of a language as a .po file.
   *
   * @param \Symfony\Component\Console\Style\StyleInterface|\Drupal\drush_language\Drush8Io $io
   *   The $io interface of the cli tool calling.
   * @param callable $t
   *   The translation function akin to t().
   * @param array $options
   *   The command options.
   *
   * @todo Implement \Drupal\locale\Form\ExportForm::buildForm
   * @todo This can be simplified once https://www.drupal.org/node/2631584
   *       lands in Drupal core.
   *
   * @throws \Exception
   *   Invalid values passed.
   */
  public function exportTranslations(
    $io,
    callable $t,
    array $options = [
      'statuses' => ['customized'],
      'langcodes' => [],
      'file' => '%langcode.po',
      'force' => TRUE,
    ]
  ) {
    // Get options.
    $opt_langcodes = $options['langcodes'];
    $opt_filepath = $options['file'];
    $opt_force_write = $options['force'];
    $opt_statuses = $options['statuses'];

    // Massage options.
    // Massage translation statuses.
    $export_statuses_allowed = [
      // internal-value => input-value.
      'customized' => 'customized',
      'not_customized' => 'not-customized',
      'not_translated' => 'not-translated',
    ];
    $opt_statuses = array_values($opt_statuses);
    if ($opt_statuses == ['all']) {
      $opt_statuses = $export_statuses_allowed;
    }
    $export_statuses_unknown = array_diff($opt_statuses, $export_statuses_allowed);
    if ($export_statuses_unknown) {
      $io->error($t('Unknown status options: @options',
        ['@options' => implode(', ', $export_statuses_unknown)]
      ));
      return;
    }
    $export_statuses_filtered = array_intersect($export_statuses_allowed, $opt_statuses);
    $export_statuses = array_fill_keys(array_keys($export_statuses_filtered), TRUE);

    // Massage file path pattern.
    if (!Path::isAbsolute($opt_filepath) && !('./' === substr($opt_filepath, 0, 2))) {
      $opt_filedir = Settings::get('custom_translations_directory');
      if (!$opt_filedir) {
        $io->error($t('Can not export, relative path given and no $settings[\'custom_translations_directory\'] defined. You can instead use an absolute filename or one starting with "./".'));
        return;
      }
      if (!Path::isAbsolute($opt_filedir)) {
        $opt_filedir = DRUPAL_ROOT . DIRECTORY_SEPARATOR . $opt_filedir;
      }
      $opt_filepath = $opt_filedir . DIRECTORY_SEPARATOR . $opt_filepath;
    }

    // Massage langcodes.
    if (!$opt_langcodes) {
      $languages = $this->languageManager->getLanguages();
      $opt_langcodes = array_keys($languages);
    }

    // Validate options.
    // Yell if more than 1 langcode and no placeholder.
    if (count($opt_langcodes) > 1 && !preg_match('/%langcode/u', $opt_filepath)) {
      $io->error($t('You must use %langcode file placeholder when exporting multiple languages.'));
      return;
    }
    // Check that all langcodes are valid, before beginning.
    foreach ($opt_langcodes as $langcode) {
      $language = $this->languageManager->getLanguage($langcode);
      if ($language == NULL) {
        $io->error($t('Unknown language: %langcode', ['%langcode' => $langcode]));
        return;
      }
    }

    // Do our work.
    foreach ($opt_langcodes as $langcode) {
      $filepath = preg_replace('/%langcode/u', $langcode, $opt_filepath);
      $language = $this->languageManager->getLanguage($langcode);

      // Check if file_path exists and is writable.
      $dir = dirname($filepath);
      if (!file_prepare_directory($dir)) {
        file_prepare_directory($dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
      }

      $reader = new PoDatabaseReader();
      $language_name = '';
      if ($language != NULL) {
        $reader->setLangcode($language->getId());
        $reader->setOptions($export_statuses);
        $languages = $this->languageManager->getLanguages();
        $language_name = isset($languages[$language->getId()]) ? $languages[$language->getId()]->getName() : '';
      }
      $item = $reader->readItem();
      if ($item || $opt_force_write) {
        $header = $reader->getHeader();
        $header->setProjectName($this->configFactory->get('system.site')->get('name'));
        $header->setLanguageName($language_name);

        $writer = new PoStreamWriter();
        $writer->setURI($filepath);
        $writer->setHeader($header);

        $writer->open();
        if ($item) {
          $writer->writeItem($item);
        }
        $writer->writeItems($reader);
        $writer->close();

        $io->success($t('Exported translations for language !langcode to file !file.', ['!langcode' => $langcode, '!file' => $filepath]));
      }
      else {
        $io->error($t('Nothing to export for language !langcode.', ['!langcode' => $langcode]));
        return;
      }
    }
    $io->success($t('Export complete.'));
  }

  /**
   * Returns error messages created while running the import.
   *
   * @return array
   *   List of messages.
   */
  public function getErrors() {
    return $this->errors;
  }

}
