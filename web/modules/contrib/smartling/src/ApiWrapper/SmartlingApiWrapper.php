<?php

/**
 * @file
 * Contains \Drupal\smartling\ApiWrapper\SmartlingApiWrapper.
 */

namespace Drupal\smartling\ApiWrapper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\State;
use Drupal\Core\Url;
use Drupal\smartling\Smartling\SmartlingApiException;
use Drupal\smartling\SmartlingSubmissionInterface;
use Drupal\smartling\Smartling\SmartlingApi;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Class SmartlingApiWrapper.
 */
class SmartlingApiWrapper implements ApiWrapperInterface {

  /**
   * The smartling.settings config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The HTTP client to pass for API SKD instance.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The smartling API sdk object.
   *
   * @var \Drupal\smartling\Smartling\SmartlingApi
   */
  protected $api;

  /**
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * This function converts Drupal locale to Smartling locale.
   *
   * @param string $locale
   *   Locale string in some format: 'en' or 'en-US'.
   *
   * @return string|null
   *   Return mapped locale string or NULL.
   */
  protected function convertLocaleDrupalToSmartling($locale) {
    // @todo Check usage, probably should use only enabled languages.
    return $this->config->get('account_info.language_mappings.' . $locale);
  }

  /**
   * Constructs SmartlingApiWrapper object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\State\State $state
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerInterface $logger, ClientInterface $http_client, State $state) {
    $this->config = $config_factory->get('smartling.settings');
    $this->logger = $logger;
    $this->httpClient = $http_client;
    $this->state = $state;
  }

  /**
   * Returns Smartling project ID.
   *
   * @return string
   *   The configured Smartling project ID.
   */
  protected function getProjectId() {
    return $this->config->get('account_info.project_id');
  }

  /**
   * {@inheritdoc}
   */
  public function getApi() {
    if (!isset($this->api)) {
      $this->api = new SmartlingApi(
        $this->config->get('account_info.key'),
        $this->config->get('account_info.project_id'),
        $this->httpClient,
        $this->config->get('account_info.api_url')
      );
    }
    return $this->api;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocaleList() {
    try {
      $data = $this->getApi()->getLocaleList();
    }
    catch (SmartlingApiException $e) {
      $this->logger->error('Failed to get locales. @error_code - @error_message', [
        '@error_code' => $e->getCode(),
        '@error_message' => $e->getMessage(),
      ]);
      return [];
    }

    $result = [];
    foreach ($data['locales'] as $locale) {
      $result[$locale['locale']] = "{$locale['name']} ({$locale['translated']})";
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function downloadFile(SmartlingSubmissionInterface $submission) {
    // @todo Fix submission usage.
    $entity_type = $submission->get('entity_type')->value;
    $entity_id = $submission->get('entity_id')->value;
    $langcode = $submission->get('target_language')->value;
    $file_name_unic = $submission->getFileName();

    $params = [
      'retrievalType' => $this->config->get('account_info.retrieval_type'),
    ];

    $this->logger->info('Downloading %file_name for entity @entity_type:@entity_id in @langcode.', [
      '%file_name' => $file_name_unic,
      '@entity_type' => $entity_type,
      '@entity_id' => $entity_id,
      '@langcode' => $langcode,
    ]);

    $locale = $this->convertLocaleDrupalToSmartling($langcode);

    try {
      $downloaded_file = $this->getApi()->downloadFile($file_name_unic, $locale, $params);
    }
    catch(SmartlingApiException $e) {
      $this->logger->error('Error downloading file:<br/>
      Project Id: @project_id <br/>
      Action: download <br/>
      URI: @file_uri <br/>
      Drupal langcode: @langcode <br/>
      Smartling Locale: @locale <br/>
      Exception (@code) @message',
        [
          '@project_id' => $this->getProjectId(),
          '@file_uri' => $file_name_unic,
          '@langcode' => $langcode,
          '@locale' => $locale,
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ]);

      return '';
    }

    return $downloaded_file;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(SmartlingSubmissionInterface $submission) {
    $file_name = $submission->getFileName();

    $s_locale = $this->convertLocaleDrupalToSmartling($submission->get('target_language')->value);

    try {
      $status = $this->getApi()->getStatus($file_name, $s_locale);
    }
    catch(SmartlingApiException $e) {
      $this->logger->error('Smartling checks status for @entity_type id - @rid: <br/>
      Project Id: @project_id <br/>
      Action: status <br/>
      URI: @file_uri <br/>
      Drupal Locale: @d_locale <br/>
      Smartling Locale: @s_locale <br/>
      Error: response code -> @code and message -> @message', array(
        '@entity_type' => $submission->get('entity_type')->value,
        '@rid' => $submission->get('entity_id')->value,
        '@project_id' => $this->getProjectId(),
        '@file_uri' => $file_name,
        '@d_locale' => $submission->get('target_language')->value,
        '@s_locale' => $s_locale,
        '@code' => $e->getCode(),
        '@message' => $e->getMessage(),
      ), TRUE);

      return [];
    }


    $this->logger->info('Smartling checks status for @entity_type id - @rid (@d_locale). approvedString = @as, completedString = @cs', [
      '@entity_type' => $submission->get('entity_type')->value,
      '@rid' => $submission->get('entity_id')->value,
      '@d_locale' => $submission->get('target_language')->value,
      '@as' => $status['approvedStringCount'],
      '@cs' => $status['completedStringCount'],
    ]);

    // If true, file translated.
    $approved = $status['approvedStringCount'];
    $completed = $status['completedStringCount'];
    $progress = ($approved > 0) ? (int) (($completed / $approved) * 100) : 0;
    $submission->set('progress', $progress);
    $submission->set('status', SmartlingSubmissionInterface::TRANSLATING);
    $submission->setChangedTime(REQUEST_TIME);

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function testConnection(array $locales) {
    $result = [];

    foreach ($locales as $langcode => $locale) {
      try {
        $server_response = $this->getApi()->getList($locale, ['limit' => 1]);
        $result[$locale] = $langcode;
      }
      catch(SmartlingApiException $e) {
        //@todo: decide if we want increase error severity
        $this->logger->warning('Connection test for project: @project_id and locale: @locale FAILED and returned the following result: @server_response.',
        [
          '@project_id' => $this->getProjectId(),
          '@locale' => $locale,
          '@server_response' => $e->getMessage(),
        ]);
        $result[$locale] = FALSE;
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function uploadFile($file_path, $file_name, $file_type, array $locales) {
    // Make sure file type is allowed.
    $this->assertAllowedFileType($file_type);

    $locales_to_approve = $upload_params = [];
    foreach ($locales as $locale) {
      $locales_to_approve[] = $this->convertLocaleDrupalToSmartling($locale);
    }

    $upload_params = [
      'fileUri' => $file_name,
      'fileType' => $file_type,
      'approved' => 0,
      'overwriteApprovedLocales' => 0,
    ];

    if ($this->config->get('account_info.auto_authorize_content')) {
      $upload_params['approved'] = TRUE;
    }
    if ($this->config->get('account_info.callback_url_use')) {
      $upload_params['callbackUrl'] = Url::fromRoute('smartling.push_callback', ['cron_key' => $this->state->get('system.cron_key')])->setAbsolute()->toString();
    }

    try {
      //@todo: didn't we miss any status handling here, as "upload_result" is not used anywhere
      $upload_result = $this->getApi()->uploadFile($file_path, $file_name, $file_type, $upload_params);
    }
    catch(SmartlingApiException $e) {
      $this->logger->error('Smartling failed to upload xml file: <br/>
          Project Id: @project_id <br/>
          Action: upload <br/>
          URI: @file_uri <br/>
          Drupal Locale: @d_locale <br/>
          Smartling Locale: @s_locale <br/>
          Exception (@code) @message
          Upload params: @upload_params',
        [
          '@project_id' => $this->getProjectId(),
          '@file_uri' => $file_path,
          '@d_locale' => implode('; ', $locales),
          '@s_locale' => implode('; ', $locales_to_approve),
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
          '@upload_params' => print_r($upload_params, TRUE),
        ]);

      return SMARTLING_STATUS_EVENT_FAILED_UPLOAD;
    }

    $this->logger->info('Smartling uploaded @file_name for locales: @locales', [
      '@file_name' => $file_name,
      '@locales' => implode(', ', $locales),
    ]);

    return SMARTLING_STATUS_EVENT_UPLOAD_TO_SERVICE;
  }

  /**
   * Uploads context file to Smartling and writes some logs
   *
   * @param array $data
   *
   * @return int
   *
   * @todo convert this method as well.
   */
  public function uploadContext($data) {
    $data['action'] = 'upload';

    try {
      $upload_result = $this->getApi()->uploadContext($data);
    }
    catch(SmartlingApiException $e) {
      $this->logger->error('Smartling failed to upload context for module @angular_module with message: @message', [
        '@angular_module' => $data['url'],
        '@message' => $e->getMessage(),
      ]);
      return -1;
    }

    $requestId = $upload_result->requestId;
    $data = array(
      'requestId' => $requestId,
      'action' => 'getStats'
    );

    try {
      $upload_result = $this->getApi()->getContextStats($data);
    }
    catch(SmartlingApiException $e) {
      $this->logger->error('Smartling uploaded the context, but failed to get context statistics for request: @requestId  with message: @message',
        [
          '@requestId' => $requestId,
          '@message' => $e->getMessage(),
        ]);
      return -1;
    }

    return $upload_result->updatedStringsCount;
  }

  /**
   * Ensures that file type is allowed to be used to upload.
   *
   * @param string $type
   *   The file type to assert.
   */
  protected function assertAllowedFileType($type) {
    $allowed_types = [
      ApiWrapperInterface::TYPE_XML,
      ApiWrapperInterface::TYPE_GETTEXT,
    ];
    assert(in_array($type, $allowed_types), 'File type is not allowed');
  }

}
