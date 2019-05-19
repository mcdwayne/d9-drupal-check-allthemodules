<?php

namespace Drupal\translation_extractor\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Class TranslationExtractor.
 *
 * @package Drupal\translation_extractor\Service
 */
class TranslationExtractor implements TranslationExtractorInterface {

  use StringTranslationTrait;

  /**
   * The currently processed request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Drupal's language manager service.
   *
   * @var LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Private temp store object.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * Cache service provided by Drupal.
   *
   * @var CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * A list of available modules.
   *
   * @var \Drupal\Core\Extension\Extension[]
   */
  protected $moduleList;

  /**
   * TranslationExtractor constructor.
   *
   * @param LanguageManagerInterface $language_manager
   *   Drupal's language mamager.
   * @param PrivateTempStoreFactory $private_temp_store
   *   Private temp store object.
   * @param CacheBackendInterface $cache_backend
   *   Cache service provided by Drupal.
   */
  public function __construct(
    RequestStack $request_stack,
    LanguageManagerInterface $language_manager,
    PrivateTempStoreFactory $private_temp_store,
    CacheBackendInterface $cache_backend
  ) {
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->languageManager = $language_manager;
    $this->privateTempStore = $private_temp_store->get('translation_extractor');
    $this->cacheBackend = $cache_backend;

    if (($cache = $this->cacheBackend->get('translation_extractor.moduleList')) === FALSE) {
      $this->moduleList = array_filter(
        system_rebuild_module_data(),
        function (Extension $module) {
          return !preg_match('~^core~', $module->getPath());
        }
      );
      $this->cacheBackend->set('translation_extractor.moduleList', $this->moduleList, time() + 120);
    }
    else {
      $this->moduleList = $cache->data;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];
    $routes['translation_extractor.scanResults'] = new Route(
      '/admin/config/regional/translation_extractor/{module}/results/{language}',
      [
        '_title' => 'Scan results',
        '_controller' => 'translation_extractor.scanner:scanResults',
        'language' => $this->languageManager->getCurrentLanguage()->getId(),
      ],
      [
        '_permission'  => 'use translation_extractor',
        'module' => '\\w+',
        'language' => '\\w+',
      ]
    );
    return $routes;
  }

  /**
   * {@inheritdoc}
   */
  public function scan(ImmutableConfig $settings, $module) {
    // Extract the settings.
    $fileTypesToScan = $settings->get('fileExtensions');
    $searchPatterns = $settings->get('searchPatterns');

    // Prepare the file mask.
    $mask = sprintf('~(?:%s)~i', implode('|', array_map(function ($ext) {
      return preg_quote($ext, '~');
    }, $fileTypesToScan)));

    // Get the module data.
    $module = $this->moduleList[$module];

    // The directory prefix.
    $moduleDirectory = sprintf('%s/%s', DRUPAL_ROOT, $module->getPath());

    // Scan the given directory for files matching the mask.
    $files = file_scan_directory($moduleDirectory, $mask);

    // Prepare a container to hold the scan results.
    $translationStrings = [];

    // Process each file found.
    foreach ($files as $file) {

      // Get the file's contents.
      $content = file_get_contents($file->uri);

      // Apply each defined pattern to the source.
      foreach ($searchPatterns as $patternDefinition) {

        // Apply the current pattern.
        preg_match_all($patternDefinition['pattern'], $content, $matches, PREG_OFFSET_CAPTURE);

        // Remember the results (if any).
        if (!empty($matches[$patternDefinition['match']])) {

          // For easier reading...
          $matchesFound = &$matches[$patternDefinition['match']];

          // Preprocess the offsets.
          foreach ($matchesFound as &$match) {

            // Calculate the line number of the match.
            list($before) = str_split($content, $match[1]);
            $line_number = strlen($before) - strlen(str_replace(PHP_EOL, '', $before)) + 1;
            $match[1] = sprintf(
              '#: %s:%d',
              str_replace("$moduleDirectory/", '', $file->uri),
              $line_number
            );
          }

          // Merge the new results.
          $translationStrings = array_merge($translationStrings, $matchesFound);
        }
      }
    }

    // Save the strings found.
    $this->privateTempStore->set('moduleScanned', $module);
    $this->privateTempStore->set('translationStrings', $translationStrings);
  }

  /**
   * {@inheritdoc}
   */
  public function scanResults() {

    // Get the strings that matched the patterns.
    $translationStringsFound = $this->privateTempStore->get('translationStrings');

    // Determine the language that was requested for the translations.
    $langcode = $this->currentRequest->attributes->get('language');
    $languageRequested = $this->languageManager->getLanguage($langcode);

    // Get the native language names.
    $nativeLanguages = $this->languageManager->getNativeLanguages();

    // Get all installed languages for the language selector.
    $languagesInstalled = $this->languageManager->getLanguages();
    array_walk($languagesInstalled, function (Language &$item) {
      $item = $item->getName();
    });

    // Prepare the PO template.
    $poTemplate = [
      '#',
      sprintf(
        '# %s translations for Module "%s"',
        ucfirst($nativeLanguages[$langcode]->getName()),
        $this->privateTempStore->get('moduleScanned')->info['name']
      ),
      '#',
    ];

    // Process each string found.
    foreach ($translationStringsFound as $string) {
      $poTemplate[] = '';
      $poTemplate[] = $string[1];
      $poTemplate[] = sprintf('msgid "%s"', preg_replace('~"(?<!\\\\)~', '\\"', $string[0]));

      $translatedString = $this->t($string[0], [], ['langcode' => $langcode]);

      $poTemplate[] = sprintf(
        'msgstr "%s"',
        $translatedString == $string[0] ? '' : $translatedString
      );
    }

    // Return the processed results.
    return [
      'results' => [
        '#type' => 'textarea',
        '#title' => $this->t('Scan results'),
        '#description' => $this->t('Save as %pofilename', [
          '%pofilename' => sprintf(
            '%1$s/files/translations/%1$s.%2$s.po',
            $this->privateTempStore->get('moduleScanned')->getName(),
            $languageRequested->getId()
          ),
        ]),
        '#description_display' => 'after',
        '#value' => implode(PHP_EOL, $poTemplate),
        '#rows' => 30,
        '#attributes' => [
          'readonly' => 'readonly',
        ],
      ],
      'languageSwitcher' => [
        '#type' => 'select',
        '#id' => 'languageSwitcher',
        '#title' => $this->t('Language'),
        '#description' => $this->t('Select the language to generate the PO file for.'),
        '#description_display' => 'before',
        '#options' => $languagesInstalled,
        '#value' => $langcode,
      ],
      '#attached' => [
        'library' => ['translation_extractor/resultpage'],
      ],
    ];
  }

}
