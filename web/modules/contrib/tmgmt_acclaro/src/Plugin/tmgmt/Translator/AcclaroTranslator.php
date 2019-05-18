<?php

namespace Drupal\tmgmt_acclaro\Plugin\tmgmt\Translator;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\tmgmt\ContinuousTranslatorInterface;
use Drupal\tmgmt\Entity\RemoteMapping;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt\SourcePreviewInterface;
use Drupal\tmgmt\TMGMTException;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt_file\Format\FormatManager;
use Drupal\tmgmt_file\Plugin\tmgmt_file\Format\Xliff;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Acclaro translation plugin controller.
 *
 * @TranslatorPlugin(
 *   id = "acclaro",
 *   label = @Translation("Acclaro"),
 *   description = @Translation("Expert Translation and Localization Services by Acclaro."),
 *   logo = "icons/acclaro.svg",
 *   ui = "Drupal\tmgmt_acclaro\AcclaroTranslatorUi",
 * )
 */
class AcclaroTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface, ContinuousTranslatorInterface {
  use StringTranslationTrait;

  /**
   * Translation service URL.
   */
  const PRODUCTION_URL = 'https://my.acclaro.com';

  /**
   * Translation sandbox service URL.
   */
  const SANDBOX_URL = 'https://apisandbox.acclaro.com';

  /**
   * Translation service API version.
   *
   * @var string
   */
  const API_VERSION = 'api2';

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
   * List of supported languages by Acclaro.
   *
   * @var string[]
   */
  protected $supportedRemoteLanguages = [];

  /**
   * Constructs an Acclaro Translator object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle HTTP client.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\tmgmt_file\Format\FormatManager $format_manager
   *   The TMGMT file format manager.
   */
  public function __construct(ClientInterface $client, array $configuration, $plugin_id, array $plugin_definition, FormatManager $format_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
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
      $container->get('plugin.manager.tmgmt_file.format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    $defaults = parent::defaultSettings();
    $defaults['export_format'] = 'xlf';
    // Enable CDATA for content encoding in File translator.
    $defaults['xliff_cdata'] = TRUE;
    $defaults['xliff_processing'] = FALSE;
    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    $this->requestJobItemsTranslation($job->getItems());
    if (!$job->isRejected()) {
      $job->submitted('Job has been successfully submitted for translation.');
    }
  }

  /**
   * Executes a request against Acclaro API.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   The translator.
   * @param string $path
   *   Resource path.
   * @param array $parameters
   *   (optional) Parameters to send to Acclaro service.
   * @param string $method
   *   (optional) HTTP method (GET, POST...). Defaults to GET.
   *
   * @return array
   *   Response array from Acclaro.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   */
  protected function doRequest(TranslatorInterface $translator, $path, array $parameters = [], $method = 'GET') {
    if ($translator->getSetting('use_sandbox')) {
      $url = self::SANDBOX_URL . '/' . self::API_VERSION . '/' . $path;
    }
    else {
      $url = self::PRODUCTION_URL . '/' . self::API_VERSION . '/' . $path;
    }

    try {
      // Add the authorization token.
      $options['headers']['Authorization'] = 'Bearer ' . $translator->getSetting('token');
      if ($method == 'GET') {
        $options['query'] = $parameters;
      }
      else {
        $options += $parameters;
      }
      // Make a request.
      $response = $this->client->request($method, $url, $options);
    }
    catch (BadResponseException $e) {
      $response = $e->getResponse();
      throw new TMGMTException('Unable to connect to Acclaro service due to following error: @error', ['@error' => $response->getReasonPhrase()], $response->getStatusCode());
    }

    $body = $response->getBody()->getContents();
    $received_data = json_decode($body, TRUE);
    // In case JSON decoding fails, return the plain body.
    if (!$received_data) {
      return $body;
    }

    if (!$received_data['success']) {
      throw new TMGMTException('Acclaro service returned validation error: #%code %error',
        [
          '%code' => $received_data['errorCode'],
          '%error' => $received_data['errorMessage'],
        ]);
    }

    return isset($received_data['data']) ? $received_data['data'] : $received_data;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $source_language) {
    $target_languages = [];
    $language_pairs = $this->getLanguagePairs($translator, $source_language);

    // Parse languages.
    foreach ($language_pairs as $language_pair) {
      $target_language = $language_pair['target']['code'];
      $target_languages[$target_language] = $target_language;
    }

    return $target_languages;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedRemoteLanguages(TranslatorInterface $translator) {
    try {
      $supported_languages = $this->doRequest($translator, 'GetLanguages');
      // Parse languages.
      foreach ($supported_languages as $language) {
        $this->supportedRemoteLanguages[$language['code']] = $language['code'];
      }
      return $this->supportedRemoteLanguages;
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedLanguagePairs(TranslatorInterface $translator) {
    $language_pairs = [];

    try {
      $supported_language_pairs = $this->getLanguagePairs($translator);
      // Build a mapping of source and target language pairs.
      foreach ($supported_language_pairs as $language) {
        $language_pairs[] = [
          'source_language' => $language['source']['code'],
          'target_language' => $language['target']['code'],
        ];
      }
    }
    catch (\Exception $e) {
      return [];
    }

    return $language_pairs;
  }

  /**
   * Gets the available language pairs.
   */
  public function getLanguagePairs(TranslatorInterface $translator, $source_language = '') {
    $options = [];
    if (!empty($source_language)) {
      $options['sourcelang'] = $source_language;
    }

    try {
      return $this->doRequest($translator, 'GetLanguagePairs', $options);
    }
    catch (TMGMTException $e) {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkAvailable(TranslatorInterface $translator) {
    if ($translator->getSetting('token')) {
      return AvailableResult::yes();
    }
    return AvailableResult::no($this->t('@translator is not available. Make sure it is properly <a href=:configured>configured</a>.', [
      '@translator' => $translator->label(),
      ':configured' => $translator->toUrl()->toString(),
    ]));
  }

  /**
   * Gets the account info.
   */
  public function getAccount(TranslatorInterface $translator) {
    try {
      return $this->doRequest($translator, 'GetAccount');
    }
    catch (TMGMTException $e) {
      return [];
    }
  }

  /**
   * Creates an order.
   */
  public function createOrder(TranslatorInterface $translator, $name, $comment = NULL, $duedate = NULL) {
    $query = [
      'name' => $name,
      'comments' => $comment,
      'duedate' => $duedate ? date_iso8601(strtotime($duedate)) : NULL,
    ];
    return $this->doRequest($translator, 'CreateOrder', $query);
  }

  /**
   * Gets the order data.
   */
  public function getOrder(TranslatorInterface $translator, $order_id) {
    return $this->doRequest($translator, 'GetOrder', ['orderid' => $order_id]);
  }

  /**
   * Sets an order into complete state via simulate call.
   */
  public function simulateOrderComplete(TranslatorInterface $translator, $order_id) {
    return $this->doRequest($translator, 'SimulateOrderComplete', ['orderid' => $order_id]);
  }

  /**
   * Sets a file into preview state via simulate call.
   */
  public function simulatePreviewReady(TranslatorInterface $translator, $order_id, $file_id) {
    return $this->doRequest($translator, 'SimulatePreviewReady', ['orderid' => $order_id, 'fileid' => $file_id]);
  }

  /**
   * Adds a review URL.
   */
  public function addReviewUrl(TranslatorInterface $translator, $order_id, $file_id, $url) {
    return $this->doRequest($translator, 'AddReviewURL', [
      'orderid' => $order_id,
      'fileid' => $file_id,
      'url' => $url,
    ]);
  }

  /**
   * Adds a comment to the file.
   */
  public function addFileComment(TranslatorInterface $translator, $order_id, $file_id, $comment) {
    return $this->doRequest($translator, 'AddFileComment', [
      'orderid' => $order_id,
      'fileid' => $file_id,
      'comment' => $comment,
    ]);
  }

  /**
   * Adds a comment to the order.
   */
  public function addOrderComment(TranslatorInterface $translator, $order_id, $comment) {
    return $this->doRequest($translator, 'AddOrderComment', [
      'orderid' => $order_id,
      'comment' => $comment,
    ]);
  }

  /**
   * Uploads a file, and attaches it to the given order.
   */
  public function sendSourceFile(TranslatorInterface $translator, $source, $target, $order_id, $job_item_id, $file_name, $xliff_content) {
    $options = [
      'multipart' => [
        [
          'name' => 'orderid',
          'contents' => $order_id,
        ],
        [
          'name' => 'sourcelang',
          'contents' => $source,
        ],
        [
          'name' => 'targetlang',
          'contents' => $target,
        ],
        [
          'name' => 'clientref',
          'contents' => $job_item_id,
        ],
        [
          'name' => 'file',
          'contents' => $xliff_content,
          'filename' => $file_name,
        ],
      ],
    ];
    return $this->doRequest($translator, 'SendSourceFile', $options, 'POST');
  }

  /**
   * Submits the order.
   */
  public function submitOrder(TranslatorInterface $translator, $order_id) {
    return $this->doRequest($translator, 'SubmitOrder', ['orderid' => $order_id]);
  }

  /**
   * Gets the file info.
   */
  public function getFileInfo(TranslatorInterface $translator, $order_id) {
    return $this->doRequest($translator, 'GetFileInfo', ['orderid' => $order_id]);
  }

  /**
   * Request that Acclaro invoke the supplied URL when status changes.
   */
  public function requestOrderCallback(TranslatorInterface $translator, $order_id, $job_id) {
    $url = Url::fromRoute('tmgmt_acclaro.order_callback', [
      'tmgmt_job' => $job_id,
      'order_id' => $order_id,
    ])->setAbsolute()->toString();
    return $this->doRequest($translator, 'RequestOrderCallback', [
      'orderid' => $order_id,
      'url' => $url,
    ]);
  }

  /**
   * Request that Acclaro no longer invoke the supplied URL when order changes.
   */
  public function cancelOrderCallback(TranslatorInterface $translator, $order_id, $job_id) {
    $url = Url::fromRoute('tmgmt_acclaro.order_callback', [
      'tmgmt_job' => $job_id,
      'order_id' => $order_id,
    ])->setAbsolute()->toString();
    return $this->doRequest($translator, 'CancelOrderCallback', [
      'orderid' => $order_id,
      'url' => $url,
    ]);
  }

  /**
   * Request that Acclaro invoke the supplied URL when file changes.
   */
  public function requestFileCallback(TranslatorInterface $translator, $order_id, $file_id, $job_item_id) {
    $url = Url::fromRoute('tmgmt_acclaro.file_callback', [
      'tmgmt_job_item' => $job_item_id,
      'order_id' => $order_id,
      'file_id' => $file_id,
    ])->setAbsolute()->toString();
    return $this->doRequest($translator, 'RequestFileCallback', [
      'orderid' => $order_id,
      'fileid' => $file_id,
      'url' => $url,
    ]);
  }

  /**
   * Request that Acclaro no longer invoke the supplied URL when file changes.
   */
  public function cancelFileCallback(TranslatorInterface $translator, $order_id, $file_id, $job_item_id) {
    $url = Url::fromRoute('tmgmt_acclaro.file_callback', [
      'tmgmt_job_item' => $job_item_id,
      'order_id' => $order_id,
      'file_id' => $file_id,
    ])->setAbsolute()->toString();
    return $this->doRequest($translator, 'CancelFileCallback', [
      'orderid' => $order_id,
      'fileid' => $file_id,
      'url' => $url,
    ]);
  }

  /**
   * Retrieves the translated file.
   */
  public function getFile(TranslatorInterface $translator, $order_id, $file_id) {
    return $this->doRequest($translator, 'GetFile', ['orderid' => $order_id, 'fileid' => $file_id]);
  }

  /**
   * Gets the file status.
   */
  public function getFileStatus(TranslatorInterface $translator, $order_id, $file_id) {
    return $this->doRequest($translator, 'GetFileStatus', ['orderid' => $order_id, 'fileid' => $file_id]);
  }

  /**
   * Updates translation for the given source file, order ID.
   *
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The job item.
   * @param string $order_id
   *   The order ID.
   * @param string $file_id
   *   The file ID.
   * @param bool $add_message
   *   (optional) Add status message flag. Defaults to TRUE.
   *
   * @return bool
   *   Returns if the translation is fetched. Otherwise, FALSE.
   *
   * @throws \Exception|TMGMTException
   *   Throws an exception in case of invalid translation.
   */
  public function updateTranslation(JobItemInterface $job_item, $order_id, $file_id, $add_message = TRUE) {
    $translator = $job_item->getTranslator();
    /** @var \Drupal\tmgmt_acclaro\Plugin\tmgmt\Translator\AcclaroTranslator $translator_plugin */
    $translator_plugin = $job_item->getTranslatorPlugin();
    // Get the file status info.
    $file_status = $translator_plugin->getFileStatus($translator, $order_id, $file_id);

    switch ($file_status['status']) {
      case 'complete':
        // If the status is "complete", remote translation is ready.
        $translator_plugin->importTranslation($translator, $job_item, $order_id, $file_status['targetfile']);
        return TRUE;

      case 'canceled':
        // Abort the item if the file is cancelled.
        $this->abortJobItem($translator, $job_item, $order_id, $file_id);
        return FALSE;

      case 'preview':
        $this->handleTranslationPreview($translator, $job_item, $order_id, $file_status['previewfile']);
        return FALSE;

      default:
        if ($add_message) {
          $job_item->addMessage('The remote translation has changed the status to %status.', ['%status' => $file_status['status']]);
        }
        return FALSE;
    }
  }

  /**
   * Handles translation preview.
   */
  public function handleTranslationPreview(TranslatorInterface $translator, JobItemInterface $job_item, $order_id, $preview_file_id) {
    $this->importTranslationPreview($translator, $job_item, $order_id, $preview_file_id);
    $source_plugin = $job_item->getSourcePlugin();
    // Add the preview URL if supported by the source plugin.
    if ($source_plugin instanceof SourcePreviewInterface) {
      $preview_url = $source_plugin->getPreviewUrl($job_item)->setAbsolute()->toString();
      $this->addReviewUrl($translator, $order_id, $preview_file_id, $preview_url);
    }
    else {
      // Notify Acclaro that we are unable to provide a preview URL.
      $message = $this->t('This file does not support live web preview.', [], ['langcode' => $job_item->getSourceLangCode()]);
      $this->addFileComment($translator, $order_id, $preview_file_id, (string) $message);
    }
  }

  /**
   * Imports translation preview.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   The translator.
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The job item.
   * @param string $order_id
   *   The order ID.
   * @param string $preview_file_id
   *   The preview file ID.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   *   Throws TMGMTException in case of invalid data.
   */
  public function importTranslationPreview(TranslatorInterface $translator, JobItemInterface $job_item, $order_id, $preview_file_id) {
    $translation_preview = $this->getFile($translator, $order_id, $preview_file_id);
    $xliff = $this->formatManager->createInstance('xlf');

    // Validate and import the translation preview.
    if ($this->isValidTranslation($xliff, $translation_preview, $job_item)) {
      if ($data = $xliff->import($translation_preview, FALSE)) {
        // Add translation preview into preliminary state.
        $job_item->getJob()->addTranslatedData($data, NULL, TMGMT_DATA_ITEM_STATE_PRELIMINARY);
        $job_item->addMessage('The remote translation has changed the status to %status.', ['%status' => 'preview']);
      }
      else {
        throw new TMGMTException('Could not process received translation data for the preview file @file_id.', ['@file_id' => $preview_file_id]);
      }
    }
  }

  /**
   * Validates translation data.
   *
   * @param \Drupal\tmgmt_file\Plugin\tmgmt_file\Format\Xliff $xliff
   *   The xliff converter.
   * @param string $translation
   *   The translation data.
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The job item.
   *
   * @return bool
   *   Returns TRUE if the translation is valid.
   *
   * @throws \Drupal\tmgmt\TMGMTException
   *   Throws TMGMTException if translation is not valid.
   */
  public function isValidTranslation(Xliff $xliff, $translation, JobItemInterface $job_item) {
    if (!$validated_job = $xliff->validateImport($translation, FALSE)) {
      throw new TMGMTException('Failed to validate translation preview, import aborted.');
    }
    elseif ($validated_job->id() != $job_item->getJob()->id()) {
      throw new TMGMTException('The remote translation preview (Job ID: @target_job_id) does not match the current job ID @job_id.', [
        '@target_job_job' => $validated_job->id(),
        '@job_id' => $job_item->getJob()->id(),
      ], 'error');
    }

    return TRUE;
  }

  /**
   * Fetches translations for job items of a given job.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   A job containing job items that translations will be fetched for.
   */
  public function fetchTranslations(JobInterface $job) {
    /** @var \Drupal\tmgmt\Entity\RemoteMapping[] $remotes */
    $remotes = RemoteMapping::loadByLocalData($job->id());
    $translated = 0;

    // Check for if there are completed translations.
    foreach ($remotes as $remote) {
      $job_item = $remote->getJobItem();
      $file_id = $remote->getRemoteIdentifier1();
      $order_id = $remote->getRemoteIdentifier2();

      try {
        if ($status = $this->updateTranslation($job_item, $order_id, $file_id, FALSE)) {
          $translated++;
        }
      }
      catch (TMGMTException $tmgmt_exception) {
        $job_item->addMessage($tmgmt_exception->getMessage());
      }
      catch (\Exception $e) {
        watchdog_exception('tmgmt_acclaro', $e);
      }
    }

    // Provide a message about translated items.
    if ($translated == 0) {
      drupal_set_message($this->t('No job item has been translated yet.'), 'warning');
    }
    else {
      $untranslated = count($remotes) - $translated;
      if ($untranslated > 0) {
        $job->addMessage('Fetched translations for @translated job items, @untranslated items are not translated yet.', [
          '@translated' => $translated,
          '@untranslated' => $untranslated,
        ]);
      }
      else {
        $job->addMessage('Fetched translations for @translated job items.', ['@translated' => $translated]);
      }
      tmgmt_write_request_messages($job);
    }
  }

  /**
   * Simulates complete orders from Acclaro.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The translation job.
   */
  public function simulateCompleteOrder(JobInterface $job) {
    $remote_mappings = $job->getRemoteMappings();
    $remote_mapping = reset($remote_mappings);

    if ($remote_mapping) {
      $order_id = $remote_mapping->getRemoteIdentifier2();

      try {
        $order = $this->simulateOrderComplete($job->getTranslator(), $order_id);
        if ($order['status'] == 'complete') {
          $job->addMessage($this->t('The order (@order_id) has been marked as completed by using simulate order complete command.', ['@order_id' => $order_id]));
        }
      }
      catch (TMGMTException $e) {
        drupal_set_message($e->getMessage(), 'error');
      }
    }
  }

  /**
   * Simulates translation previews from Acclaro.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The translation job.
   */
  public function simulateTranslationPreview(JobInterface $job) {
    $remote_mappings = $job->getRemoteMappings();
    // Get the first job item.
    $remote_mapping = reset($remote_mappings);

    /** @var \Drupal\tmgmt\JobItemInterface $job_item */
    $job_item = $remote_mapping->getJobItem();
    /** @var \Drupal\tmgmt\SourcePreviewInterface $source_plugin */
    $source_plugin = $job_item->getSourcePlugin();
    $translator = $job->getTranslator();

    $file_id = $remote_mapping->getRemoteIdentifier1();
    $order_id = $remote_mapping->getRemoteIdentifier2();

    try {
      $translation_preview = $this->simulatePreviewReady($translator, $order_id, $file_id);
      if ($translation_preview['success']) {
        // Check that original file status has changed into preview state.
        $file_status = $this->getFileStatus($translator, $order_id, $file_id);
        if ($file_status['status'] != 'preview') {
          throw new TMGMTException('The remote translation is not in the preview status.');
        }

        $this->importTranslationPreview($translator, $job_item, $order_id, $file_status['previewfile']);
        $preview_url = $source_plugin->getPreviewUrl($job_item)->setAbsolute()->toString();
        drupal_set_message($this->t('%job_item has been set in the preview mode. Follow the <a href=":preview_url">preview URL</a>.', [
          '%job_item' => $job_item->label(),
          ':preview_url' => $preview_url,
        ]));
      }
    }
    catch (TMGMTException $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
  }

  /**
   * Retrieves the translation.
   */
  public function importTranslation(TranslatorInterface $translator, JobItemInterface $job_item, $order_id, $target_file_id) {
    // Check the target file status.
    $target_file_status = $this->getFileStatus($translator, $order_id, $target_file_id);
    // Abort the import in case its status is not "complete".
    if ($target_file_status['status'] != 'complete') {
      throw new TMGMTException('The remote translation is not completed yet.');
    }

    $xliff = $this->formatManager->createInstance('xlf');
    // Fetch and validate the remote translation.
    $translation = $this->getFile($translator, $order_id, $target_file_id);
    $validated_job = $xliff->validateImport($translation, FALSE);
    if (!$validated_job) {
      throw new TMGMTException('Failed to validate remote translation, import aborted.');
    }
    elseif ($validated_job->id() != $job_item->getJob()->id()) {
      throw new TMGMTException('The remote translation (File ID: @file_id, Job ID: @target_job_id) does not match the current job ID @job_id.', [
        '@file_id' => $target_file_id,
        '@target_job_job' => $validated_job->id(),
        '@job_id' => $job_item->getJob()->id(),
      ], 'error');
    }
    else {
      if ($data = $xliff->import($translation, FALSE)) {
        // The remote translation was successfully imported.
        $job_item->getJob()->addTranslatedData($data, NULL, TMGMT_DATA_ITEM_STATE_TRANSLATED);
        $job_item->addMessage('The translation has been received.');
      }
      else {
        throw new TMGMTException('Could not process received translation data for the target file @file_id.', ['@file_id' => $target_file_id]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function requestJobItemsTranslation(array $job_items) {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $first_item = reset($job_items);
    $job = $first_item->getJob();
    try {
      $source_language = $job->getRemoteSourceLanguage();
      $target_language = $job->getRemoteTargetLanguage();
      $translator = $job->getTranslator();
      $xliff = $this->formatManager->createInstance('xlf');

      // Create a new order and add its callback route.
      $name = $job->getSetting('name') ? $job->getSetting('name') : $job->label() . ' (' . $job->id() . ')';
      $order = $this->createOrder($translator, $name, $job->getSetting('comment'), $job->getSetting('duedate'));
      $order_id = $order['orderid'];
      $job->addMessage('Order (@order_id) has been created.', ['@order_id' => $order_id]);
      $this->requestOrderCallback($translator, $order_id, $job->id());

      /** @var \Drupal\tmgmt\JobItemInterface $job_item */
      foreach ($job_items as $job_item) {
        $job_item_id = $job_item->id();
        // Export content as XLIFF.
        $xliff_content = $xliff->export($job, ['tjiid' => ['value' => $job_item_id]]);
        // Build a file name.
        $file_name = "JobID_{$job->id()}_JobItemID_{$job_item_id}_{$job->getSourceLangcode()}_{$job->getTargetLangcode()}.xlf";

        // Upload a file.
        $file_status = $this->sendSourceFile($translator, $source_language, $target_language, $order_id, $job_item_id, $file_name, $xliff_content);
        $job_item->active();
        // Set a callback route in order to get updates on file status changes.
        $this->requestFileCallback($translator, $order_id, $file_status['fileid'], $job_item_id);
        // Add a remote reference (file ID) to the job item.
        $job_item->addRemoteMapping(NULL, $file_status['fileid'], ['remote_identifier_2' => $order_id]);
      }

      // Submit the order for translation.
      $this->submitOrder($translator, $order_id);
    }
    catch (TMGMTException $e) {
      $job->rejected('Job has been rejected with following error: @error', ['@error' => $e->getMessage()], 'error');
    }
  }

  /**
   * Aborts a job item and send a comment to Acclaro.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   The translator.
   * @param \Drupal\tmgmt\JobItemInterface $job_item
   *   The job item to abort.
   * @param string $order_id
   *   The order ID.
   * @param string $file_id
   *   The file ID.
   */
  public function abortJobItem(TranslatorInterface $translator, JobItemInterface $job_item, $order_id, $file_id) {
    if (!$job_item->isAborted()) {
      try {
        $this->addFileComment($translator, $order_id, $file_id, 'CANCEL FILE');
        // Abort the job item.
        $variables = [
          '@source' => $job_item->getSourceLabel(),
          ':source_url' => $job_item->getSourceUrl() ? $job_item->getSourceUrl()->toString() : (string) $job_item->getJob()->toUrl(),
        ];
        $job_item->setState(JobItemInterface::STATE_ABORTED, 'The translation of <a href=":source_url">@source</a> has been aborted by the user.', $variables);
        // As file was aborted, tell Acclaro that no more updates are needed.
        $this->cancelFileCallback($translator, $order_id, $file_id, $job_item->id());
      }
      catch (TMGMTException $e) {
        $variables = [
          '@source' => $job_item->getSourceLabel(),
          ':source_url' => $job_item->getSourceUrl() ? $job_item->getSourceUrl()->toString() : (string) $job_item->getJob()->toUrl(),
          '@error' => $e->getMessage(),
        ];
        $job_item->addMessage('Failed to abort <a href=":source_url">@source</a> item. @error', $variables, 'error');
      }
    }
  }

  /**
   * Aborts normal jobs.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *   The job to abort.
   *
   * @return bool
   *   Returns TRUE if job was aborted. Otherwise, FALSE.
   */
  public function abortJob(JobInterface $job) {
    // Do not allow continuous jobs abortion.
    if ($job->isContinuous()) {
      return FALSE;
    }

    $order_id = NULL;
    $translator = $job->getTranslator();
    /** @var \Drupal\tmgmt\Entity\RemoteMapping $remote */
    foreach ($job->getRemoteMappings() as $remote) {
      $job_item = $remote->getJobItem();
      $file_id = $remote->getRemoteIdentifier1();
      $order_id = $remote->getRemoteIdentifier2();

      // Abort the job item and notify Acclaro via file comment.
      $this->abortJobItem($translator, $job_item, $order_id, $file_id);
    }

    try {
      // Abort job in the current system.
      $aborted = FALSE;
      if ($job->isAbortable()) {
        $job->setState(JobInterface::STATE_ABORTED, 'Translation job has been aborted.');
        $aborted = TRUE;
      }
      // Notify Acclaro about aborted order.
      if ($order_id && $aborted) {
        $this->addOrderComment($translator, $order_id, 'CANCEL ORDER');
        // As order was aborted, tell Acclaro that no more updates are needed.
        $this->cancelOrderCallback($translator, $order_id, $job->id());
        return TRUE;
      }
    }
    catch (TMGMTException $e) {
      $job->addMessage('Failed to abort translation job. @error', ['@error' => $e->getMessage()], 'error');
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function abortTranslation(JobInterface $job) {
    drupal_set_message($this->t('Please contact your Acclaro project manager with the order cancellation request.'), 'warning');
    return $this->abortJob($job);
  }

}
