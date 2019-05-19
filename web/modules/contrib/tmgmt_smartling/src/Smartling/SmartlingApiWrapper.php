<?php

/**
 * @file
 * SmartlingApiWrapper.php.
 */

namespace Drupal\tmgmt_smartling\Smartling;

use DateTime;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\tmgmt\Entity\Translator;
use Drupal\tmgmt\JobInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Smartling\AuditLog\Params\CreateRecordParameters;
use Smartling\Batch\Params\CreateBatchParameters;
use Smartling\Exceptions\SmartlingApiException;
use Smartling\Jobs\Params\CancelJobParameters;
use Smartling\Jobs\Params\CreateJobParameters;
use Smartling\Jobs\Params\ListJobsParameters;
use Smartling\Jobs\Params\UpdateJobParameters;
use Smartling\TranslationRequests\Params\CreateTranslationRequestParams;
use Smartling\TranslationRequests\Params\SearchTranslationRequestParams;
use Smartling\TranslationRequests\Params\UpdateTranslationRequestParams;
use Smartling\ProgressTracker\Params\RecordParameters;

/**
 * Class SmartlingApiWrapper
 * @package Drupal\tmgmt_smartling\Smartling
 */
class SmartlingApiWrapper {

  /**
   * @var SmartlingApiFactory
   */
  private $apiFactory;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var array
   */
  private $settings = [];

  /**
   * SmartlingApiWrapper constructor.
   * @param \Drupal\tmgmt_smartling\Smartling\SmartlingApiFactory $api_factory
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(SmartlingApiFactory $api_factory, LoggerInterface $logger) {
    require_once __DIR__ . '/../../vendor/autoload.php';

    $this->apiFactory = $api_factory;
    $this->logger = $logger;
  }

  /**
   * @param string $api_type
   * @return mixed
   * @throws Exception
   * @throws SmartlingApiException
   */
  public function getApi($api_type = 'file') {
    return $this->apiFactory->create($this->settings, $api_type);
  }

  /**
   * Returns list of jobs by name.
   *
   * @param null $name
   * @param array $statuses
   * @return array
   */
  public function listJobs($name = NULL, array $statuses = []) {
    $result = [];

    try {
      $params = new ListJobsParameters();

      if (!empty($name)) {
        $params->setName($name);
      }

      if (!empty($statuses)) {
        $params->setStatuses($statuses);
      }

      $result = $this->getApi('jobs')->listJobs($params);
    }
    catch (SmartlingApiException $e) {
      $this->logger->error('Smartling failed to fetch list of available jobs:<br/>Error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Creates Smartling job.
   *
   * @param $name
   * @param $description
   * @param \DateTime|NULL $due_date
   * @return array
   */
  public function createJob($name, $description, DateTime $due_date = NULL) {
    $job_id = NULL;

    try {
      $params = new CreateJobParameters();
      $params->setName($name);
      $params->setDescription($description);

      if (!empty($due_date)) {
        $params->setDueDate($due_date);
      }

      $response = $this->getApi('jobs')->createJob($params);
      $job_id = $response['translationJobUid'];

      $this->logger->info('Smartling created a job:<br/>
      Id: @id<br/>
      Name: @name<br/>
      Description: @description<br/>
      Due date (UTC): @due_date<br/>', [
        '@id' => $job_id,
        '@name' => $name,
        '@description' => $description,
        '@due_date' => empty($due_date) ? '' : $due_date->format('Y-m-d H:i'),
      ]);
    }
    catch (Exception $e) {
      $this->logger->error('Smartling failed to create a job:<br/>
      Name: @name<br/>
      Description: @description<br/>
      Due date (UTC): @due_date<br/>
      Error: @error', [
        '@name' => $name,
        '@description' => $description,
        '@due_date' => empty($due_date) ? '' : $due_date->format('Y-m-d H:i'),
        '@error' => $e->getMessage(),
      ]);
    }

    return $job_id;
  }

  /**
   * Updates Smartling job.
   *
   * @param $job_id
   * @param $name
   * @param $description
   * @param \DateTime|NULL $due_date
   * @return array
   */
  public function updateJob($job_id, $name, $description, DateTime $due_date = NULL) {
    $result = NULL;

    try {
      $params = new UpdateJobParameters();
      $params->setName($name);

      if (!empty($description)) {
        $params->setDescription($description);
      }

      if (!empty($due_date)) {
        $params->setDueDate($due_date);
      }

      $result = $this->getApi('jobs')->updateJob($job_id, $params);

      $this->logger->info('Smartling updated a job:<br/>
      Id: @id<br/>
      Name: @name<br/>
      Description: @description<br/>
      Due date (UTC): @due_date<br/>', [
        '@id' =>  $result['translationJobUid'],
        '@name' => $name,
        '@description' => $description,
        '@due_date' => empty($due_date) ? '' : $due_date->format('Y-m-d H:i'),
      ]);
    }
    catch (Exception $e) {
      $this->logger->error('Smartling failed to update a job:<br/>
      Name: @name<br/>
      Description: @description<br/>
      Due date (UTC): @due_date<br/>
      Error: @error', [
        '@name' => $name,
        '@description' => $description,
        '@due_date' => empty($due_date) ? '' : $due_date->format('Y-m-d H:i'),
        '@error' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Returns job.
   *
   * @param $job_id
   * @return array
   */
  public function getJob($job_id) {
    $result = [];

    try {
      $result = $this->getApi('jobs')->getJob($job_id);
    }
    catch (SmartlingApiException $e) {
      $this->logger->error('Smartling failed to fetch a job:<br/>
      Job id: @job_id
      Error: @error', [
        '@job_id' => $job_id,
        '@error' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Cancels job.
   *
   * @param $job_id
   * @param string $reason
   * @return array
   */
  public function cancelJob($job_id, $reason = '') {
    $result = [];

    try {
      $params = new CancelJobParameters();
      $params->setReason($reason);
      $result = $this->getApi('jobs')->cancelJobSync($job_id, $params);
    }
    catch (SmartlingApiException $e) {
      $this->logger->error('Smartling failed to cancel a job:<br/>
      Job id: @job_id
      Error: @error', [
          '@job_id' => $job_id,
          '@error' => $e->getMessage(),
        ]
      );
    }

    return $result;
  }

  /**
   * Creates Smartling batch.
   *
   * @param $job_id
   * @param $authorize
   * @return array
   */
  public function createBatch($job_id, $authorize) {
    $batch_uid = NULL;

    try {
      $params = new CreateBatchParameters();
      $params->setTranslationJobUid($job_id);
      $params->setAuthorize($authorize);
      $response = $this->getApi('batch')->createBatch($params);
      $batch_uid = $response['batchUid'];

      $this->logger->info('Smartling created a batch:<br/>Id: @id', [
        '@id' => $batch_uid,
      ]);
    }
    catch (SmartlingApiException $e) {
      $this->logger->error('Smartling failed to create a batch:<br/>Error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $batch_uid;
  }

  /**
   * Executes Smartling batch.
   *
   * @param $batch_uid
   * @return bool
   */
  public function executeBatch($batch_uid) {
    $result = FALSE;

    try {
      $this->getApi('batch')->executeBatch($batch_uid);

      $this->logger->info('Smartling executed a batch:<br/>Id: @id', [
        '@id' => $batch_uid,
      ]);
    }
    catch (SmartlingApiException $e) {
      $this->logger->error('Smartling failed to execute a batch @batch_uid:<br/>Error: @error', [
        '@batch_uid' => $batch_uid,
        '@error' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * @param $spaceId
   * @param $objectId
   * @param $ttl
   * @param $data
   * @return null
   */
  public function createFirebaseRecord($spaceId, $objectId, $ttl, $data) {
    $result = NULL;

    try {
      $params = new RecordParameters();
      $params->setTtl($ttl);
      $params->setData($data);

      $this->getApi('progress')->createRecord($spaceId, $objectId, $params);

      $result = TRUE;
    }
    catch (SmartlingApiException $e) {
      $this->logger->error('Smartling failed to create a record:<br/>Error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Create translation request.
   *
   * @param $bucketName
   * @param \Smartling\TranslationRequests\Params\CreateTranslationRequestParams $params
   *
   * @return mixed
   */
  public function createTranslationRequest($bucketName, CreateTranslationRequestParams $params) {
    $result = FALSE;

    try {
      $result = $this->getApi('translation_request')->createTranslationRequest($bucketName, $params);
    }
    catch (SmartlingApiException $e) {
      $this->logger->error('Smartling failed to create translation request:<br/>
      Bucket name: @bucket
      Params: @params
      Error: @error', [
        '@bucket' => $bucketName,
        '@params' => json_encode($params->exportToArray()),
        '@error' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * @param $bucketName
   * @param $translationRequestUid
   * @param \Smartling\TranslationRequests\Params\UpdateTranslationRequestParams $params
   *
   * @return null
   */
  public function updateTranslationRequest($bucketName, $translationRequestUid, UpdateTranslationRequestParams $params) {
    $result = FALSE;

    try {
      $result = $this->getApi('translation_request')->updateTranslationRequest($bucketName, $translationRequestUid, $params);
    }
    catch (SmartlingApiException $e) {
      $this->logger->error('Smartling failed to update translation request:<br/>
      Bucket name: @bucket
      Params: @params
      Error: @error', [
        '@bucket' => $bucketName,
        '@params' => json_encode($params->exportToArray()),
        '@error' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Returns translation request by search params.
   *
   * @param $bucketName
   * @param \Smartling\TranslationRequests\Params\SearchTranslationRequestParams $params
   *
   * @return mixed
   */
  public function searchTranslationRequest($bucketName, SearchTranslationRequestParams $params) {
    $result = [];

    try {
      $response = $this->getApi('translation_request')->searchTranslationRequests($bucketName, $params);
      $result = $response['items'];
    }
    catch (SmartlingApiException $e) {
      $this->logger->error('Smartling failed to search translation request:<br/>
      Bucket name: @bucket
      Params: @params
      Error: @error', [
        '@bucket' => $bucketName,
        '@params' => json_encode($params->exportToArray()),
        '@error' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Delete file.
   *
   * @param $fileUri
   * @return bool
   */
  public function deleteFile($fileUri) {
    $result = FALSE;

    try {
      $result = $this->getApi('file')->deleteFile($fileUri);

      $this->logger->info('Smartling deleted a file in Dashboard: @uri', [
        '@uri' => $fileUri,
      ]);
    }
    catch (SmartlingApiException $e) {
      $this->logger->error('Smartling failed to delete a file in Dashboard: @uri.<br/>Error: @error', [
        '@uri' => $fileUri,
        '@error' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * @param \Drupal\tmgmt\JobInterface $job
   * @param \Drupal\tmgmt\Entity\Translator $translator
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param $action_type
   */
  public function createAuditLogRecord(
    JobInterface $job = NULL,
    Translator $translator = NULL,
    AccountProxyInterface $current_user,
    $action_type
  ) {
    try {
      // Provider settings can be grabbed from job or from translator (support
      // for job events and translator events).
      $provider_settings = [];
      $locale_mapping = [];
      $resolved_translator = NULL;

      if (!empty($job)) {
        $resolved_translator = $job->getTranslator();
      }

      if (empty($resolved_translator) && !empty($translator)) {
        $resolved_translator = $translator;
      }

      // Retrieve provider settings.
      if (!empty($resolved_translator)) {
        $locale_mapping = $resolved_translator->getRemoteLanguagesMappings();
        $provider_settings = $resolved_translator->getSettings();

        // Do not send sensitive data.
        unset(
          $provider_settings['project_id'],
          $provider_settings['user_id'],
          $provider_settings['token_secret'],
          $provider_settings['basic_auth']
        );
      }

      $params = new CreateRecordParameters();
      $params
        ->setActionTime(time())
        ->setActionType($action_type)
        ->setEnvId(php_uname('n'))
        ->setClientUserId($current_user->id())
        ->setClientUserEmail((string) $current_user->getEmail())
        ->setClientUserName((string) $current_user->getAccountName())
        ->setClientData('provider_settings', $provider_settings)
        ->setClientData('locale_mappings', $locale_mapping);

      if ($job) {
        $params->setFileUri((string) $job->getTranslatorPlugin()->getFileName($job))
          ->setClientData('tmgmt_job_id', (string) $job->id())
          ->setClientData('tmgmt_job_label', (string) $job->label())
          ->setClientData('tmgmt_job_translator', (string) $job->getTranslatorLabel())
          ->setClientData('tmgmt_job_state', (string) $job->getState())
          ->setClientData('tmgmt_job_created', (string) $job->getCreatedTime())
          ->setClientData('tmgmt_job_changed', (string) $job->getChangedTime())
          ->setSourceLocaleId((string) $job->getSourceLangcode())
          ->setTargetLocaleIds([(string) $job->getRemoteTargetLanguage()])
          ->setTranslationJobUid((string) $job->getSetting('job_id'))
          ->setTranslationJobName((string) $job->getSetting('job_name'))
          ->setTranslationJobDueDate((string) $job->getSetting('due_date'))
          ->setTranslationJobAuthorize($job->getSetting('authorize'))
          ->setBatchUid((string) $job->getSetting('batch_uid'))
          ->setClientData('batch_execute_on_job', (string) $job->getSetting('batch_execute_on_job'));
      }

      $this->getApi('audit')->createProjectLevelLogRecord($params);
    }
    catch (Exception $e) {
      $this->logger->error('Smartling failed to create an audit log record.<br/>Error: @error.', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * @param array $settings
   */
  public function setSettings($settings) {
    $this->settings = $settings;
  }

}
