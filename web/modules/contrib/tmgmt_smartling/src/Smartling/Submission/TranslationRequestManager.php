<?php

namespace Drupal\tmgmt_smartling\Smartling\Submission;

use Drupal\Core\State\StateInterface;
use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt_smartling\Smartling\SmartlingApiWrapper;
use Exception;
use Psr\Log\LoggerInterface;
use Smartling\TranslationRequests\Params\CreateTranslationRequestParams;
use Smartling\TranslationRequests\Params\CreateTranslationSubmissionParams;
use Smartling\TranslationRequests\Params\SearchTranslationRequestParams;
use Smartling\TranslationRequests\Params\TranslationSubmissionStates;
use Smartling\TranslationRequests\Params\UpdateTranslationRequestParams;
use Smartling\TranslationRequests\Params\UpdateTranslationSubmissionParams;

/**
 * Class TranslationRequestManager.
 */
class TranslationRequestManager {

  /**
   * @var SmartlingApiWrapper
   */
  private $apiWrapper;

  /**
   * @var StateInterface
   */
  private $state;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @return string
   */
  protected function getBucketName() {
    return $this->state->get('tmgmt_smartling.bucket_name', 'tmgmt_smartling_default_bucket_name');
  }

  protected function getSubmitterName(JobInterface $job) {
    if (PHP_SAPI === 'cli') {
      return 'cron';
    }

    return $job->getOwner()->getAccountName();
  }

  /**
   * Submission constructor.
   *
   * @param \Drupal\tmgmt_smartling\Smartling\SmartlingApiWrapper $apiWrapper
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(SmartlingApiWrapper $apiWrapper, StateInterface $state, LoggerInterface $logger) {
    $this->apiWrapper = $apiWrapper;
    $this->state = $state;
    $this->logger = $logger;
  }

  /**
   * @param \Drupal\tmgmt\JobInterface $job
   */
  protected function initApiWrapper(JobInterface $job) {
    $this->apiWrapper->setSettings($job->getTranslator()->getSettings());
  }

  /**
   * Check if job ready for download.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   * @return mixed
   */
  public function isTranslationRequestReadyForDownload(JobInterface $job) {
    $this->initApiWrapper($job);

    $translation_request = $this->getTranslationRequest($job);

    if (empty($translation_request) || $this->isTranslationSubmissionMissed($translation_request)) {
      $this->logger->error('Translation request does not contain translation submission. Translation request = @translation_request', [
        '@translation_request' => json_encode($translation_request)
      ]);

      $result = FALSE;
    }
    else {
      $result = $translation_request['translationSubmissions'][0]['state'] == TranslationSubmissionStates::STATE_TRANSLATED;
    }

    if (empty($result)) {
      $this->logger->info('Translation request submission is not ready for download. Translation request = @translation_request', [
        '@translation_request' => json_encode($translation_request)
      ]);
    }

    return $result;
  }

  /**
   * Send jobs to Submission service.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   * @return mixed
   */
  public function upsertTranslationRequest(JobInterface $job) {
    $this->initApiWrapper($job);

    $translation_request = $this->getTranslationRequest($job);

    if (empty($translation_request) || $this->isTranslationSubmissionMissed($translation_request)) {
      $result = $this->createNewTranslationRequest($job);
    }
    else {
      $result = $this->updateExistingTranslationRequest($job, $translation_request);
    }

    return $result;
  }

  /**
   * Returns translation request.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *
   * @return string
   */
  public function getTranslationRequest(JobInterface $job) {
    $this->initApiWrapper($job);

    $asset_key = ['tmgmt_job_id' => $job->id()];

    $search_params = new SearchTranslationRequestParams();
    $search_params->setOriginalAssetKey($asset_key);
    $search_params->setTargetAssetKey($asset_key);
    $search_params->setFileUri($job->getTranslatorPlugin()->getFileName($job));
    $search_params->setTargetLocaleId($job->getRemoteTargetLanguage());

    $translation_requests = $this->apiWrapper->searchTranslationRequest($this->getBucketName($job), $search_params);

    return empty($translation_requests) ? $translation_requests : $translation_requests[0];
  }

  /**
   * Creates translation request.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   *
   * @return bool
   */
  protected function createNewTranslationRequest(JobInterface $job) {
    $asset_key = ['tmgmt_job_id' => $job->id()];

    $create_submission_params = new CreateTranslationSubmissionParams();
    $create_submission_params
      ->setTargetAssetKey($asset_key)
      ->setTargetLocaleId($job->getRemoteTargetLanguage())
      ->setCustomTranslationData([
        'batch_uid' => $job->getSetting('batch_uid'),
        'batch_execute_on_job' => $job->getSetting('batch_execute_on_job')
      ])
      ->setState(TranslationSubmissionStates::STATE_NEW)
      ->setSubmitterName($this->getSubmitterName($job));

    $create_request_params = new CreateTranslationRequestParams();
    $create_request_params
      ->setOriginalAssetKey($asset_key)
      ->setTitle($job->label())
      ->setFileUri($job->getTranslatorPlugin()->getFileName($job))
      ->setOriginalLocaleId($job->getSourceLangcode())
      ->addTranslationSubmission($create_submission_params);

    return $this->apiWrapper->createTranslationRequest($this->getBucketName($job), $create_request_params);
  }

  /**
   * Updates translation request.
   *
   * @param \Drupal\tmgmt\JobInterface $job
   * @param array $translation_request
   *
   * @return bool
   */
  protected function updateExistingTranslationRequest(JobInterface $job, array $translation_request) {
    $update_submission_params = new UpdateTranslationSubmissionParams();
    $update_submission_params
      ->setSubmitterName($this->getSubmitterName($job))
      ->setCustomTranslationData([
        'batch_uid' => $job->getSetting('batch_uid'),
        'batch_execute_on_job' => $job->getSetting('batch_execute_on_job')
      ])
      ->setState(TranslationSubmissionStates::STATE_NEW)
      ->setTranslationSubmissionUid($translation_request['translationSubmissions'][0]['translationSubmissionUid']);

    $update_request_params = new UpdateTranslationRequestParams();
    $update_request_params
      ->setTitle($job->label())
      ->addTranslationSubmission($update_submission_params);

    return $this->apiWrapper->updateTranslationRequest($this->getBucketName($job), $translation_request['translationRequestUid'], $update_request_params);
  }

  /**
   * @param \Drupal\tmgmt\JobInterface $job
   * @param $translation_request
   *
   * @return mixed
   */
  public function commitSuccessfulUpload(JobInterface $job, $translation_request) {
    $this->initApiWrapper($job);

    if ($this->isTranslationSubmissionMissed($translation_request)) {
      return FALSE;
    }

    $update_submission_params = new UpdateTranslationSubmissionParams();
    $update_submission_params
      ->setSubmittedDate(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setTranslationSubmissionUid($translation_request['translationSubmissions'][0]['translationSubmissionUid']);

    $update_request_params = new UpdateTranslationRequestParams();
    $update_request_params
      ->addTranslationSubmission($update_submission_params);

    return $this->apiWrapper->updateTranslationRequest(
      $this->getBucketName($job),
      $translation_request['translationRequestUid'],
      $update_request_params
    );
  }

  /**
   * @param \Drupal\tmgmt\JobInterface $job
   * @param array $translation_request
   * @param \Exception $e
   *
   * @return mixed
   */
  public function commitError(JobInterface $job, array $translation_request, Exception $e) {
    $this->initApiWrapper($job);

    if ($this->isTranslationSubmissionMissed($translation_request)) {
      return FALSE;
    }

    $update_submission_params = new UpdateTranslationSubmissionParams();
    $update_submission_params
      ->setState(TranslationSubmissionStates::STATE_FAILED)
      ->setLastErrorMessage(mb_substr($e->getMessage() . ': ' . $e->getTraceAsString(), 0, 1024))
      ->setTranslationSubmissionUid($translation_request['translationSubmissions'][0]['translationSubmissionUid']);

    $update_request_params = new UpdateTranslationRequestParams();
    $update_request_params
      ->addTranslationSubmission($update_submission_params);

    return $this->apiWrapper->updateTranslationRequest(
      $this->getBucketName($job),
      $translation_request['translationRequestUid'],
      $update_request_params
    );
  }

  /**
   * @param \Drupal\tmgmt\JobInterface $job
   * @param array $translation_request
   * @return mixed
   */
  public function commitSuccessfulDownload(JobInterface $job, array $translation_request) {
    $this->initApiWrapper($job);

    if ($this->isTranslationSubmissionMissed($translation_request)) {
      return FALSE;
    }

    $update_submission_params = new UpdateTranslationSubmissionParams();
    $update_submission_params
      ->setLastExportedDate(new \DateTime('now', new \DateTimeZone('UTC')))
      ->setTranslationSubmissionUid($translation_request['translationSubmissions'][0]['translationSubmissionUid']);

    if ($translation_request['translationSubmissions'][0]['state'] === TranslationSubmissionStates::STATE_TRANSLATED) {
      $update_submission_params
        ->setState(TranslationSubmissionStates::STATE_COMPLETED);
    }

    $update_request_params = new UpdateTranslationRequestParams();
    $update_request_params
      ->addTranslationSubmission($update_submission_params);

    return $this->apiWrapper->updateTranslationRequest(
      $this->getBucketName($job),
      $translation_request['translationRequestUid'],
      $update_request_params
    );
  }

  /**
   * @param array $translation_request
   *
   * @return bool
   */
  public function isTranslationSubmissionMissed(array $translation_request) {
    $is_translation_submission_is_missed = empty($translation_request['translationSubmissions'][0]);

    if ($is_translation_submission_is_missed) {
      $this->logger->error('Translation request does not have submission. Translation request = @translation_request', [
        '@translation_request' => json_encode($translation_request)
      ]);
    }

    return $is_translation_submission_is_missed;
  }

}
