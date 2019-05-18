<?php

namespace Drupal\tmgmt_capita\Plugin\tmgmt\Translator;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt_file\Format\FormatManager;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Capita translation plugin controller.
 *
 * @TranslatorPlugin(
 *   id = "capita",
 *   label = @Translation("Capita TI"),
 *   description = @Translation("Capita Translation and Interpreting (Capita TI) provides translation and interpreting services to the commercial and public sector."),
 *   logo = "icons/capita.svg",
 *   ui = "Drupal\tmgmt_capita\CapitaTranslatorUi",
 * )
 */
class CapitaTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * Translation production URL.
   */
  const PRODUCTION_URL = 'https://api.capitatranslationinterpreting.com/api/v1.0';

  /**
   * Translation staging URL.
   */
  const STAGING_URL = 'https://api.capitatranslationinterpreting.com/staging/api/v1.0';

  /**
   * List of supported languages by Capita.
   *
   * @var string[]
   */
  protected $supportedRemoteLanguages = [];

  /**
   * Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The format manager.
   *
   * @var \Drupal\tmgmt_file\Format\FormatManager
   */
  protected $formatManager;

  /**
   * Constructs a Capita Translator object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle HTTP client.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger for this channel.
   * @param \Drupal\tmgmt_file\Format\FormatManager $format_manager
   *   The TMGMT file format manager.
   */
  public function __construct(ClientInterface $client, array $configuration, $plugin_id, array $plugin_definition, LoggerInterface $logger, FormatManager $format_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
    $this->logger = $logger;
    $this->formatManager = $format_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('http_client'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('capita'),
      $container->get('plugin.manager.tmgmt_file.format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    $defaults = parent::defaultSettings();
    $defaults['export_format'] = 'xlf';
    // Enable CDATA for content encoding.
    $defaults['xliff_cdata'] = TRUE;
    $defaults['xliff_processing'] = FALSE;
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    // Export content as XLIFF.
    $xliff = $this->formatManager->createInstance('xlf', ['target' => 'source']);
    $xliff_content = $xliff->export($job);
    // Build a file name.
    $file_name = "Capita_JobID_{$job->id()}_{$job->getSourceLangcode()}_{$job->getTargetLangcode()}.dpxliff";

    try {
      $response = $this->submitTranslationJob($job, $file_name, $xliff_content);
      $job->set('reference', $response['RequestId']);
      $job->submitted('The translation job has been submitted.');
    }
    catch (TMGMTException $e) {
      $this->logger->error($e->getMessage());
      $job->rejected('Job has been rejected with following error: @error', ['@error' => $e->getMessage()], 'error');
    }
  }

  /**
   * Submits a translation request to Capita.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The translation job to submit.
   * @param string $file_name
   *   The file name.
   * @param string $xliff_content
   *   The XLIFF file content.
   *
   * @return array
   *   A response array.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   *   Throws an exception in case of a submission error.
   */
  public function submitTranslationJob(JobInterface $job, $file_name, $xliff_content) {
    $options = [
      'multipart' => [
        [
          'name' => 'CustomerName',
          'contents' => $job->getSetting('customer_name'),
        ],
        [
          'name' => 'ContactName',
          'contents' => $job->getSetting('contact_name'),
        ],
        [
          'name' => 'SourceLanguageCode',
          'contents' => $job->getRemoteSourceLanguage(),
        ],
        [
          'name' => 'TargetLanguageCodes',
          'contents' => $job->getRemoteTargetLanguage(),
        ],
        [
          'name' => 'Title',
          'contents' => $job->label(),
        ],
        [
          'name' => 'DeliveryDate',
          'contents' => $job->getSetting('due_date'),
        ],
        [
          'name' => 'files',
          'contents' => $xliff_content,
          'filename' => $file_name,
        ],
      ],
    ];
    return $this->doRequest($job->getTranslator(), 'requests', $options, 'POST');
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {
    $languages = $this->getSupportedRemoteLanguages($translator);

    // Support any language pairs. Remove the source language to prevent
    // duplicates.
    if (array_key_exists($source_language, $languages)) {
      unset($languages[$source_language]);
      return $languages;
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedLanguagePairs(TranslatorInterface $translator) {
    if (!empty($this->supportedLanguagePairs)) {
      return $this->supportedLanguagePairs;
    }

    // Build a mapping of supported language pairs based on currently enabled
    // languages.
    $site_languages = \Drupal::languageManager()->getLanguages();
    $language_pairs = [];
    foreach ($site_languages as $source_langcode => $source_language_info) {
      $source = ['source_language' => $translator->mapToRemoteLanguage($source_langcode)];
      foreach ($site_languages as $target_langcode => $target_language_info) {
        // Skip the same pairs.
        if ($source_langcode === $target_langcode) {
          continue;
        }
        $language_pairs[] = $source + [
          'target_language' => $translator->mapToRemoteLanguage($target_langcode),
        ];
      }
    }

    return $language_pairs;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedRemoteLanguages(TranslatorInterface $translator) {
    if (!empty($this->supportedRemoteLanguages)) {
      return $this->supportedRemoteLanguages;
    }

    try {
      $supported_languages = $this->doRequest($translator, 'languages');
      // Parse languages.
      foreach ($supported_languages as $language) {
        $this->supportedRemoteLanguages[$language['IsoCode']] = $language['LanguageName'];
      }
      return $this->supportedRemoteLanguages;
    }
    catch (TMGMTException $e) {
      return [];
    }
  }

  /**
   * Fetches translations for the given job.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   A job containing job items that translations will be fetched for.
   */
  public function fetchTranslations(JobInterface $job) {
    $request_id = $job->getReference();
    try {
      $request_data = $this->doRequest($job->getTranslator(), "requests/$request_id");

      // Get the translation if the request is completed and there are files.
      if ($this->isTranslationCompleted($request_data)) {
        // Extract the document ID from the first final document.
        $document_id = $request_data['Documents'][0]['FinalDocuments'][0]['DocumentId'];
        // Get the actual translation data.
        $translation_data = $this->doRequest($job->getTranslator(), "documents/$document_id");

        if ($translation_data) {
          $this->importTranslation($job, $translation_data);
        }
      }
    }
    catch (\Exception $e) {
      $job->addMessage('Failed downloading translation. Message error: @error', ['@error' => $e->getMessage()], 'error');
    }
  }

  /**
   * Returns TRUE in case the requested translation is completed.
   *
   * @param array $request_data
   *   The request data.
   *
   * @return bool
   *   TRUE if the requested translation is completed. Otherwise, FALSE.
   */
  public function isTranslationCompleted(array $request_data) {
    return isset($request_data['RequestStatus']) && $request_data['RequestStatus'] === 'completed' && !empty($request_data['Documents'][0]['FinalDocuments']);
  }

  /**
   * Imports the given translation data.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The job to import translations for.
   * @param string $translation
   *   The translation data.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Throws an exception if XLF plugin does not exist.
   * @throws \Drupal\tmgmt\TMGMTException
   *   Throws an exception in case of a neeror.
   */
  public function importTranslation(JobInterface $job, $translation) {
    /** @var \Drupal\tmgmt_file\Plugin\tmgmt_file\Format\Xliff $xliff */
    $xliff = $this->formatManager->createInstance('xlf');

    if (!$xliff->validateImport($translation, FALSE)) {
      throw new TMGMTException('Failed to validate remote translation, the translation import has been aborted.');
    }

    if ($data = $xliff->import($translation, FALSE)) {
      $job->addTranslatedData($data, NULL, TMGMT_DATA_ITEM_STATE_TRANSLATED);
      $job->addMessage('The translation has been received.');
    }
    else {
      throw new TMGMTException('Could not process received translation data.');
    }
  }

  /**
   * Build a HTTP request to Capita TI service.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   The Capita translator object.
   * @param string $path
   *   The path.
   * @param array $parameters
   *   (optional) The list of parameters to send. Defaults to empty array.
   * @param string $method
   *   (optional) The HTTP method. Defaults to "GET".
   *
   * @return mixed
   *   The received data.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   *   Throws a TMGMTException exception in case of an error.
   */
  public function doRequest(TranslatorInterface $translator, $path, array $parameters = [], $method = 'GET') {
    // Build a URL depending on the environment.
    $url = $translator->getSetting('environment') === 'production' ? self::PRODUCTION_URL : self::STAGING_URL;
    $url .= "/$path";

    try {
      // Add the authorization data.
      $options['auth'] = [$translator->getSetting('username'), $translator->getSetting('password')];
      if ($method == 'GET') {
        $options['query'] = $parameters;
      }
      else {
        $options += $parameters;
      }
      // Make a HTTP request.
      $response = $this->client->request($method, $url, $options);
    }
    catch (GuzzleException $e) {
      $response = $e->getResponse();
      $data = json_decode($response->getBody(), TRUE);
      $error = isset($data['detail']) ? $data['detail'] : $response->getReasonPhrase();
      throw new TMGMTException('Capita service returned the following error message: @error', ['@error' => $error], $response->getStatusCode());
    }

    $body = $response->getBody()->getContents();
    // Parse the received data as JSON.
    if ($response->getHeaderLine('Content-Type') === 'application/json') {
      return (array) json_decode($body, TRUE);
    }

    return $body;
  }

}
