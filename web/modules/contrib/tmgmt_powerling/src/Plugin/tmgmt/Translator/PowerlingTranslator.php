<?php

namespace Drupal\tmgmt_powerling\Plugin\tmgmt\Translator;

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
 * Powerling translation plugin controller.
 *
 * @TranslatorPlugin(
 *   id = "powerling",
 *   label = "Powerling",
 *   description = "Expert Translation and Localization Services by Powerling.",
 *   logo = "icons/powerling.svg",
 *   ui = "Drupal\tmgmt_powerling\PowerlingTranslatorUi",
 * )
 */
class PowerlingTranslator extends TranslatorPluginBase implements ContainerFactoryPluginInterface, ContinuousTranslatorInterface
{
  use StringTranslationTrait;

  /**
   * Translation service URL.
   */
  const PRODUCTION_URL = 'https://cms-connector.powerling-tp.com';

  /**
   * Translation sandbox service URL.
   */
  const SANDBOX_URL = 'https://cms-connector-sb.powerling-tp.com';

  /**
   * Translation service API version.
   *
   * @var string
   */
  const API_VERSION = '1';

  /**
   * Guzzle HTTP client.
   *
   * @var ClientInterface
   */
  protected $client;

  /**
   * The format manager.
   *
   * @var FormatManager
   */
  protected $formatManager;

  /**
   * List of supported languages by Powerling.
   *
   * @var string[]
   */
  protected $supportedRemoteLanguages = [];

  /**
   * Constructs a Powerling Translator object.
   *
   * @param ClientInterface $client The Guzzle HTTP client.
   * @param array $configuration A configuration array containing information about the plugin instance.
   * @param string $pluginId The pluginId for the plugin instance.
   * @param array $pluginDefinition The plugin implementation definition.
   * @param FormatManager $formatManager The TMGMT file format manager.
   */
  public function __construct(ClientInterface $client, array $configuration, $pluginId, array $pluginDefinition, FormatManager $formatManager)
  {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->client = $client;
    $this->formatManager = $formatManager;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings()
  {
    $defaults = parent::defaultSettings();
    $defaults['export_format'] = 'xlf';
    $defaults['xliff_cdata'] = TRUE;
    $defaults['xliff_processing'] = FALSE;

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job)
  {
    $this->requestJobItemsTranslation($job->getItems());
    
    if (!$job->isRejected()) {
      $job->submitted('Job has been successfully submitted for translation.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTargetLanguages(TranslatorInterface $translator, $sourceLanguage)
  {
    $targetLanguages = [];
    $languagePairs = $this->getLanguagePairs($translator, $sourceLanguage);

    foreach ($languagePairs as $languagePair) {
      $targetLanguage = $languagePair['target']['code'];
      $targetLanguages[$targetLanguage] = $targetLanguage;
    }

    return $targetLanguages;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedRemoteLanguages(TranslatorInterface $translator)
  {
    try {
      $supportedLanguages = $this->doRequest($translator, 'languages');

      foreach ($supportedLanguages as $language) {
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
  public function getSupportedLanguagePairs(TranslatorInterface $translator)
  {
    $languagePairs = [];

    try {
      $supportedLanguagePairs = $this->getLanguagePairs($translator);

      foreach ($supportedLanguagePairs as $language) {
        $languagePairs[] = [
          'source_language' => $language['source']['code'],
          'target_language' => $language['target']['code'],
        ];
      }
    }
    catch (\Exception $e) {
      return [];
    }

    return $languagePairs;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAvailable(TranslatorInterface $translator)
  {
    if ($translator->getSetting('token')) {
      return AvailableResult::yes();
    }

    return AvailableResult::no($this->t('@translator is not available. Make sure it is properly <a href=:configured>configured</a>.', [
      '@translator' => $translator->label(),
      ':configured' => $translator->toUrl()->toString(),
    ]));
  }

  /**
   * {@inheritdoc}
   */
  public function requestJobItemsTranslation(array $jobItems)
  {
    /** @var \Drupal\tmgmt\Entity\Job $job */
    $firstItem = reset($jobItems);
    $job = $firstItem->getJob();
    
    try {
      $sourceLanguage = $job->getRemoteSourceLanguage();
      $targetLanguage = $job->getRemoteTargetLanguage();
      $translator = $job->getTranslator();
      $xliff = $this->formatManager->createInstance('xlf');
      $name = $job->getSetting('name') ? $job->getSetting('name') : $job->label() . ' (' . $job->id() . ')';
      $order = $this->createOrder($translator, $name, $job->getSetting('comment'), $job->getSetting('duedate'));
      $orderId = $order['orderid'];
      $job->addMessage('Order (@order_id) has been created.', ['@order_id' => $orderId]);
      $this->requestOrderCallback($translator, $orderId, $job->id());

      /** @var JobItemInterface $jobItem */
      foreach ($jobItems as $jobItem) {
        $jobItemId = $jobItem->id();
        $xliffContent = $xliff->export($job, ['tjiid' => ['value' => $jobItemId]]);
        $fileName = "JobID_{$job->id()}_JobItemID_{$jobItemId}_{$job->getSourceLangcode()}_{$job->getTargetLangcode()}.xlf";
        $fileStatus = $this->sendSourceFile($translator, $sourceLanguage, $targetLanguage, $orderId, $jobItemId, $fileName, $xliffContent);
        $jobItem->active();
        $this->requestFileCallback($translator, $orderId, $fileStatus['fileid'], $jobItemId);
        $jobItem->addRemoteMapping(NULL, $fileStatus['fileid'], ['remote_identifier_2' => $orderId]);
      }

      $this->submitOrder($translator, $orderId);
    }
    catch (TMGMTException $e) {
      $job->rejected('Job has been rejected with following error: @error', ['@error' => $e->getMessage()], 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function abortTranslation(JobInterface $job)
  {
    drupal_set_message('Please contact your Powerling project manager with the order cancellation request.', 'warning');

    return $this->abortJob($job);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition)
  {
    return new static(
      $container->get('http_client'),
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('plugin.manager.tmgmt_file.format')
    );
  }

  /**
   * Executes a request against Powerling API.
   *
   * @param TranslatorInterface $translator The translator.
   * @param string $path Resource path.
   * @param array $parameters (optional) Parameters to send to Powerling service.
   * @param string $method (optional) HTTP method (GET, POST...). Defaults to GET.
   * @return array Response array from Powerling.
   * @throws TMGMTException
   * @throws BadResponseException
   */
  protected function doRequest(TranslatorInterface $translator, $path, array $parameters = [], $method = 'GET')
  {
    if ($translator->getSetting('use_sandbox')) {
      $url = self::SANDBOX_URL . '/v' . self::API_VERSION . '/' . $path;
    }
    else {
      $url = self::PRODUCTION_URL . '/v' . self::API_VERSION . '/' . $path;
    }
    try {
      $options['headers']['Authorization'] = 'Bearer ' . $translator->getSetting('token');

      if ($method == 'GET') {
        $options['query'] = $parameters;
      }
      else if (array_key_exists('multipart', $parameters)) {
        $options += $parameters;
      }
      else {
        $options['form_params'] = $parameters;
      }

      $response = $this->client->request($method, $url, $options);
    }
    catch (BadResponseException $e) {
      $response = $e->getResponse();
      throw new TMGMTException('Unable to connect to Powerling service due to following error: @error', ['@error' => $response->getReasonPhrase()], $response->getStatusCode());
    }

    $body = $response->getBody()->getContents();
    $receivedData = json_decode($body, TRUE);

    if (!$receivedData) {
      return $body;
    }
    if (!$receivedData['success']) {
      throw new TMGMTException('Powerling service returned validation error: #%code %error',
        [
          '%code' => $receivedData['errorCode'],
          '%error' => $receivedData['errorMessage'],
        ]);
    }

    return isset($receivedData['data']) ? $receivedData['data'] : $receivedData;
  }

  /**
   * Gets the available language pairs.
   * @param TranslatorInterface $translator The translator.
   * @param string $sourceLanguage
   * @return array
   */
  public function getLanguagePairs(TranslatorInterface $translator, $sourceLanguage = '')
  {
    $options = [];

    if (!empty($sourceLanguage)) {
      $options['sourcelang'] = $sourceLanguage;
    }
    try {
      return $this->doRequest($translator, 'language-pairs', $options);
    }
    catch (TMGMTException $e) {
      return [];
    }
  }

  /**
   * Gets the account info.
   * @param TranslatorInterface $translator The translator.
   * @return array
   */
  public function getAccount(TranslatorInterface $translator)
  {
    try {
      return $this->doRequest($translator, 'account');
    }
    catch (TMGMTException $e) {
      return [];
    }
  }

  /**
   * Creates an order.
   * @param TranslatorInterface $translator The translator.
   * @param $name
   * @param null $comment
   * @param null $duedate
   * @return array
   * @throws TMGMTException
   */
  public function createOrder(TranslatorInterface $translator, $name, $comment = NULL, $duedate = NULL)
  {
    $query = [
      'name' => $name,
      'comments' => $comment,
      'duedate' => $duedate ? date_iso8601(strtotime($duedate)) : NULL,
    ];

    return $this->doRequest($translator, 'order/create', $query, 'POST');
  }

  /**
   * Gets the order data.
   * @param TranslatorInterface $translator The translator.
   * @param $orderId
   * @return array
   * @throws TMGMTException
   */
  public function getOrder(TranslatorInterface $translator, $orderId)
  {
    return $this->doRequest($translator, sprintf('order/%s', $orderId));
  }

  /**
   * Sets an order into complete state via simulate call.
   * @param TranslatorInterface $translator The translator.
   * @param $orderId
   * @return array
   * @throws TMGMTException
   */
  public function simulateOrderComplete(TranslatorInterface $translator, $orderId)
  {
    return $this->doRequest($translator, sprintf('order/%s/simulate-complete', $orderId), [], 'POST');
  }

  /**
   * Sets a file into preview state via simulate call.
   * @param TranslatorInterface $translator The translator.
   * @param $orderId
   * @param string $fileId The file ID.
   * @return array
   * @throws TMGMTException
   */
  public function simulatePreviewReady(TranslatorInterface $translator, $orderId, $fileId)
  {
    return $this->doRequest($translator, sprintf('order/%s/simulate-preview', $orderId), ['file_id' => $fileId], 'POST');
  }

  /**
   * Adds a review URL.
   * @param TranslatorInterface $translator The translator.
   * @param $orderId
   * @param string $fileId The file ID.
   * @param $url
   * @return array
   * @throws TMGMTException
   */
  public function addReviewUrl(TranslatorInterface $translator, $orderId, $fileId, $url)
  {
    return $this->doRequest($translator, sprintf('order/%s/add-review-url', $orderId), [
      'fileid' => $fileId,
      'url' => $url,
    ], 'POST');
  }

  /**
   * Adds a comment to the file.
   * @param TranslatorInterface $translator The translator.
   * @param $orderId
   * @param string $fileId The file ID.
   * @param $comment
   * @return array
   * @throws TMGMTException
   */
  public function addFileComment(TranslatorInterface $translator, $orderId, $fileId, $comment)
  {
    return $this->doRequest($translator, sprintf('order/%s/file/%s/add-comment', $orderId, $fileId), [
      'comment' => $comment,
    ], 'POST');
  }

  /**
   * Adds a comment to the order.
   * @param TranslatorInterface $translator The translator.
   * @param $orderId
   * @param $comment
   * @return array
   * @throws TMGMTException
   */
  public function addOrderComment(TranslatorInterface $translator, $orderId, $comment)
  {
    return $this->doRequest($translator, sprintf('order/%s/add-comment', $orderId), [
      'comment' => $comment,
    ], 'POST');
  }

  /**
   * Uploads a file, and attaches it to the given order.
   * @param TranslatorInterface $translator
   * @param $source
   * @param $target
   * @param $orderId
   * @param $jobItemId
   * @param $fileName
   * @param $xliffContent
   * @return array
   * @throws TMGMTException
   */
  public function sendSourceFile(TranslatorInterface $translator, $source, $target, $orderId, $jobItemId, $fileName, $xliffContent)
  {
    $options = [
      'multipart' => [
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
          'contents' => $jobItemId,
        ],
        [
          'name' => 'file',
          'contents' => $xliffContent,
          'filename' => $fileName,
        ],
      ],
    ];

    return $this->doRequest($translator, sprintf('order/%s/upload-file', $orderId), $options, 'POST');
  }

  /**
   * Submits the order.
   * @param TranslatorInterface $translator The translator.
   * @param string $orderId The order ID.
   * @return array
   * @throws TMGMTException
   */
  public function submitOrder(TranslatorInterface $translator, $orderId)
  {
    return $this->doRequest($translator, sprintf('order/%s/submit', $orderId), [], 'POST');
  }

  /**
   * Gets the file info.
   * @param TranslatorInterface $translator The translator.
   * @param string $orderId The order ID.
   * @return array
   * @throws TMGMTException
   */
  public function getFileInfo(TranslatorInterface $translator, $orderId)
  {
    return $this->doRequest($translator, sprintf('order/%s/file-info', $orderId));
  }

  /**
   * Request that Powerling invoke the supplied URL when status changes.
   * @param TranslatorInterface $translator The translator.
   * @param string $orderId The order ID.
   * @param $jobId
   * @return array
   * @throws TMGMTException
   */
  public function requestOrderCallback(TranslatorInterface $translator, $orderId, $jobId)
  {
    $url = Url::fromRoute('tmgmt_powerling.order_callback', [
      'tmgmt_job' => $jobId,
      'order_id' => $orderId,
    ])->setAbsolute()->toString();

    return $this->doRequest($translator, sprintf('order/%s/request-callback', $orderId), [
      'url' => $url,
    ], 'POST');
  }

  /**
   * Request that Powerling no longer invoke the supplied URL when order changes.
   * @param TranslatorInterface $translator The translator.
   * @param string $orderId The order ID.
   * @param $jobId
   * @return array
   * @throws TMGMTException
   */
  public function cancelOrderCallback(TranslatorInterface $translator, $orderId, $jobId)
  {
    $url = Url::fromRoute('tmgmt_powerling.order_callback', [
      'tmgmt_job' => $jobId,
      'order_id' => $orderId,
    ])->setAbsolute()->toString();

    return $this->doRequest($translator, sprintf('order/%s/cancel-callback', $orderId), [
      'url' => $url,
    ], 'POST');
  }

  /**
   * Request that Powerling invoke the supplied URL when file changes.
   * @param TranslatorInterface $translator The translator.
   * @param string $orderId The order ID.
   * @param string $fileId The file ID.
   * @param $jobItemId
   * @return array
   * @throws TMGMTException
   */
  public function requestFileCallback(TranslatorInterface $translator, $orderId, $fileId, $jobItemId)
  {
    $url = Url::fromRoute('tmgmt_powerling.file_callback', [
      'tmgmt_job_item' => $jobItemId,
      'order_id' => $orderId,
      'file_id' => $fileId,
    ])->setAbsolute()->toString();

    return $this->doRequest($translator, sprintf('order/%s/file/%s/request-callback', $orderId, $fileId), [
      'url' => $url,
    ], 'POST');
  }

  /**
   * Request that Powerling no longer invoke the supplied URL when file changes.
   * @param TranslatorInterface $translator The translator.
   * @param string $orderId The order ID.
   * @param string $fileId The file ID.
   * @param $jobItemId
   * @return array
   * @throws TMGMTException
   */
  public function cancelFileCallback(TranslatorInterface $translator, $orderId, $fileId, $jobItemId)
  {
    $url = Url::fromRoute('tmgmt_powerling.file_callback', [
      'tmgmt_job_item' => $jobItemId,
      'order_id' => $orderId,
      'file_id' => $fileId,
    ])->setAbsolute()->toString();

    return $this->doRequest($translator, sprintf('order/%s/file/%s/cancel-callback', $orderId, $fileId), [
      'url' => $url,
    ], 'POST');
  }

  /**
   * Retrieves the translated file.
   * @param TranslatorInterface $translator The translator.
   * @param string $orderId The order ID.
   * @param string $fileId The file ID.
   * @return array
   * @throws TMGMTException
   */
  public function getFile(TranslatorInterface $translator, $orderId, $fileId)
  {
    return $this->doRequest($translator, sprintf('order/%s/file/%s', $orderId, $fileId));
  }

  /**
   * Gets the file status.
   * @param TranslatorInterface $translator The translator.
   * @param string $orderId The order ID.
   * @param string $fileId The file ID.
   * @return array
   * @throws TMGMTException
   */
  public function getFileStatus(TranslatorInterface $translator, $orderId, $fileId)
  {
    return $this->doRequest($translator, sprintf('order/%s/file/%s/status', $orderId, $fileId));;
  }

  /**
   * Updates translation for the given source file, order ID.
   *
   * @param JobItemInterface $jobItem The job item.
   * @param string $orderId The order ID.
   * @param string $fileId The file ID.
   * @param bool $addMessage (optional) Add status message flag. Defaults to TRUE.
   * @return bool Returns if the translation is fetched. Otherwise, FALSE.
   * @throws \Exception|TMGMTException Throws an exception in case of invalid translation.
   */
  public function updateTranslation(JobItemInterface $jobItem, $orderId, $fileId, $addMessage = TRUE)
  {
    $translator = $jobItem->getTranslator();
    /** @var PowerlingTranslator $translatorPlugin */
    $translatorPlugin = $jobItem->getTranslatorPlugin();
    $fileStatus = $translatorPlugin->getFileStatus($translator, $orderId, $fileId);

    if ($fileStatus['status'] === 'complete') {
      $translatorPlugin->importTranslation($translator, $jobItem, $orderId, $fileStatus['targetfile']);

      return TRUE;
    }
    else if ($fileStatus['status'] === 'canceled') {
      $this->abortJobItem($translator, $jobItem, $orderId, $fileId);
      
      return FALSE;
    }
    else if ($fileStatus['status'] === 'preview') {
      $this->handleTranslationPreview($translator, $jobItem, $orderId, $fileStatus['previewfile']);

      return FALSE;
    }
    if ($addMessage) {
      $jobItem->addMessage('The remote translation has changed the status to %status.', ['%status' => $fileStatus['status']]);
    }

    return FALSE;
  }

  /**
   * Handles translation preview.
   * @param TranslatorInterface $translator The translator.
   * @param JobItemInterface $jobItem The job item.
   * @param string $orderId The order ID.
   * @param string $previewFileId The preview file ID.
   * @throws TMGMTException
   */
  public function handleTranslationPreview(TranslatorInterface $translator, JobItemInterface $jobItem, $orderId, $previewFileId)
  {
    $this->importTranslationPreview($translator, $jobItem, $orderId, $previewFileId);
    $sourcePlugin = $jobItem->getSourcePlugin();

    if ($sourcePlugin instanceof SourcePreviewInterface) {
      $previewUrl = $sourcePlugin->getPreviewUrl($jobItem)->setAbsolute()->toString();
      $this->addReviewUrl($translator, $orderId, $previewFileId, $previewUrl);
    }
    else {
      $message = $this->t('This file does not support live web preview.', [], ['langcode' => $jobItem->getSourceLangCode()]);
      $this->addFileComment($translator, $orderId, $previewFileId, (string)$message);
    }
  }

  /**
   * Imports translation preview.
   *
   * @param TranslatorInterface $translator The translator.
   * @param JobItemInterface $jobItem The job item.
   * @param string $orderId The order ID.
   * @param string $previewFileId The preview file ID.
   * @throws TMGMTException Throws TMGMTException in case of invalid data.
   */
  public function importTranslationPreview(TranslatorInterface $translator, JobItemInterface $jobItem, $orderId, $previewFileId)
  {
    $translationPreview = $this->getFile($translator, $orderId, $previewFileId);
    $xliff = $this->formatManager->createInstance('xlf');

    if (!$this->isValidTranslation($xliff, $translationPreview, $jobItem)) {
      return;
    }
    if ($data = $xliff->import($translationPreview, FALSE)) {
      $jobItem->getJob()->addTranslatedData($data, NULL, TMGMT_DATA_ITEM_STATE_PRELIMINARY);
      $jobItem->addMessage('The remote translation has changed the status to %status.', ['%status' => 'preview']);
    }
    else {
      throw new TMGMTException('Could not process received translation data for the preview file @file_id.', ['@file_id' => $previewFileId]);
    }
  }

  /**
   * Validates translation data.
   *
   * @param Xliff $xliff The xliff converter.
   * @param string $translation The translation data.
   * @param JobItemInterface $jobItem The job item.
   * @return bool Returns TRUE if the translation is valid.
   * @throws TMGMTException Throws TMGMTException if translation is not valid.
   */
  public function isValidTranslation(Xliff $xliff, $translation, JobItemInterface $jobItem)
  {
    if (!$validatedJob = $xliff->validateImport($translation, FALSE)) {
      throw new TMGMTException('Failed to validate translation preview, import aborted.');
    }
    elseif ($validatedJob->id() != $jobItem->getJob()->id()) {
      throw new TMGMTException('The remote translation preview (Job ID: @target_job_id) does not match the current job ID @job_id.', [
        '@target_job_job' => $validatedJob->id(),
        '@job_id' => $jobItem->getJob()->id(),
      ], 'error');
    }

    return TRUE;
  }

  /**
   * Fetches translations for job items of a given job.
   *
   * @param JobInterface $job A job containing job items that translations will be fetched for.
   */
  public function fetchTranslations(JobInterface $job)
  {
    /** @var RemoteMapping[] $remotes */
    $remotes = RemoteMapping::loadByLocalData($job->id());
    $translated = 0;

    foreach ($remotes as $remote) {
      $jobItem = $remote->getJobItem();
      $fileId = $remote->getRemoteIdentifier1();
      $orderId = $remote->getRemoteIdentifier2();

      try {
        if ($status = $this->updateTranslation($jobItem, $orderId, $fileId, FALSE)) {
          $translated++;
        }
      } catch (TMGMTException $tmgmt_exception) {
        $jobItem->addMessage($tmgmt_exception->getMessage());
      } catch (\Exception $e) {
        watchdog_exception('tmgmt_powerling', $e);
      }
    }
    if ($translated == 0) {
      drupal_set_message('No job item has been translated yet.', 'warning');
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
   * Simulates complete orders from Powerling.
   *
   * @param JobInterface $job The translation job.
   */
  public function simulateCompleteOrder(JobInterface $job)
  {
    $remoteMappings = $job->getRemoteMappings();
    $remoteMapping = reset($remoteMappings);

    if (!$remoteMapping) {
      return;
    }

    $orderId = $remoteMapping->getRemoteIdentifier2();

    try {
      $order = $this->simulateOrderComplete($job->getTranslator(), $orderId);

      if ($order['status'] == 'complete') {
        $job->addMessage($this->t('The order (@order_id) has been marked as completed by using simulate order complete command.', ['@order_id' => $orderId]));
      }
    }
    catch (TMGMTException $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
  }

  /**
   * Simulates translation previews from Powerling.
   *
   * @param JobInterface $job The translation job.
   * @throws TMGMTException
   */
  public function simulateTranslationPreview(JobInterface $job)
  {
    $remoteMappings = $job->getRemoteMappings();
    $remoteMapping = reset($remoteMappings);
    /** @var JobItemInterface $jobItem */
    $jobItem = $remoteMapping->getJobItem();
    /** @var \Drupal\tmgmt\SourcePreviewInterface $sourcePlugin */
    $sourcePlugin = $jobItem->getSourcePlugin();
    $translator = $job->getTranslator();
    $fileId = $remoteMapping->getRemoteIdentifier1();
    $orderId = $remoteMapping->getRemoteIdentifier2();

    try {
      $translationPreview = $this->simulatePreviewReady($translator, $orderId, $fileId);

      if ($translationPreview['success']) {
        $fileStatus = $this->getFileStatus($translator, $orderId, $fileId);

        if ($fileStatus['status'] != 'preview') {
          throw new TMGMTException('The remote translation is not in the preview status.');
        }

        $this->importTranslationPreview($translator, $jobItem, $orderId, $fileStatus['previewfile']);
        $previewUrl = $sourcePlugin->getPreviewUrl($jobItem)->setAbsolute()->toString();
        drupal_set_message($this->t('%job_item has been set in the preview mode. Follow the <a href=":preview_url">preview URL</a>.', [
          '%job_item' => $jobItem->label(),
          ':preview_url' => $previewUrl,
        ]));
      }
    } 
    catch (TMGMTException $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
  }

  /**
   * Imports the translation.
   *
   * @param TranslatorInterface $translator
   * @param JobItemInterface $jobItem The job item to import.
   * @param string $orderId The order ID.
   * @param string $targetFileId The target file ID.
   * @throws TMGMTException
   */
  public function importTranslation(TranslatorInterface $translator, JobItemInterface $jobItem, $orderId, $targetFileId)
  {
    $targetFileStatus = $this->getFileStatus($translator, $orderId, $targetFileId);

    if ($targetFileStatus['status'] != 'complete') {
      throw new TMGMTException('The remote translation is not completed yet.');
    }

    $xliff = $this->formatManager->createInstance('xlf');
    $translation = $this->getFile($translator, $orderId, $targetFileId);
    $validatedJob = $xliff->validateImport($translation, FALSE);

    if (!$validatedJob) {
      throw new TMGMTException('Failed to validate remote translation, import aborted.');
    }
    elseif ($validatedJob->id() != $jobItem->getJob()->id()) {
      throw new TMGMTException('The remote translation (File ID: @file_id, Job ID: @target_job_id) does not match the current job ID @job_id.', [
        '@file_id' => $targetFileId,
        '@target_job_job' => $validatedJob->id(),
        '@job_id' => $jobItem->getJob()->id(),
      ], 'error');
    }
    else {
      if ($data = $xliff->import($translation, FALSE)) {
        $jobItem->getJob()->addTranslatedData($data, NULL, TMGMT_DATA_ITEM_STATE_TRANSLATED);
        $jobItem->addMessage('The translation has been received.');
      } 
      else {
        throw new TMGMTException('Could not process received translation data for the target file @file_id.', ['@file_id' => $targetFileId]);
      }
    }
  }

  /**
   * Aborts a job item and send a comment to Powerling.
   *
   * @param TranslatorInterface $translator The translator.
   * @param JobItemInterface $jobItem The job item to abort.
   * @param string $orderId The order ID.
   * @param string $fileId The file ID.
   */
  public function abortJobItem(TranslatorInterface $translator, JobItemInterface $jobItem, $orderId, $fileId)
  {
    if ($jobItem->isAborted()) {
      return;
    }
    try {
      $this->addFileComment($translator, $orderId, $fileId, 'CANCEL FILE');
      $variables = [
        '@source' => $jobItem->getSourceLabel(),
        ':source_url' => $jobItem->getSourceUrl() ? $jobItem->getSourceUrl()->toString() : (string)$jobItem->getJob()->toUrl(),
      ];
      $jobItem->setState(JobItemInterface::STATE_ABORTED, 'The translation of <a href=":source_url">@source</a> has been aborted by the user.', $variables);
      $this->cancelFileCallback($translator, $orderId, $fileId, $jobItem->id());
    }
    catch (TMGMTException $e) {
      $variables = [
        '@source' => $jobItem->getSourceLabel(),
        ':source_url' => $jobItem->getSourceUrl() ? $jobItem->getSourceUrl()->toString() : (string)$jobItem->getJob()->toUrl(),
        '@error' => $e->getMessage(),
      ];
      $jobItem->addMessage('Failed to abort <a href=":source_url">@source</a> item. @error', $variables, 'error');
    }
  }

  /**
   * Aborts normal jobs.
   *
   * @param JobInterface $job The job to abort.
   * @return bool Returns TRUE if job was aborted. Otherwise, FALSE.
   * @throws TMGMTException
   */
  public function abortJob(JobInterface $job)
  {
    if ($job->isContinuous()) {
      return FALSE;
    }

    $orderId = NULL;
    $translator = $job->getTranslator();

    /** @var RemoteMapping $remote */
    foreach ($job->getRemoteMappings() as $remote) {
      $jobItem = $remote->getJobItem();
      $fileId = $remote->getRemoteIdentifier1();
      $orderId = $remote->getRemoteIdentifier2();
      $this->abortJobItem($translator, $jobItem, $orderId, $fileId);
    }
    try {
      $aborted = FALSE;

      if ($job->isAbortable()) {
        $job->setState(JobInterface::STATE_ABORTED, 'Translation job has been aborted.');
        $aborted = TRUE;
      }
      if ($orderId && $aborted) {
        $this->addOrderComment($translator, $orderId, 'CANCEL ORDER');
        $this->cancelOrderCallback($translator, $orderId, $job->id());

        return TRUE;
      }
    }
    catch (TMGMTException $e) {
      $job->addMessage('Failed to abort translation job. @error', ['@error' => $e->getMessage()], 'error');
    }

    return FALSE;
  }
}
