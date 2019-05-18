<?php

namespace Drupal\cleverreach\Component\Infrastructure;

use CleverReach\Infrastructure\Interfaces\Required\Configuration as ConfigInterface;
use Drupal;
use Drupal\cleverreach\Exception\ModuleNotInstalledException;
use Drupal\Core\Url;

/**
 * Configuration service.
 *
 * @see \CleverReach\Infrastructure\Interfaces\Required\Configuration
 */
class ConfigService implements ConfigInterface {
  const CLIENT_ID = 'nRiRL1I0WF';
  const CLIENT_SECRET = 'tdOshpUD6LvSq9h0RiXklkcDcYFUYCAr';

  const MODULE_NAME = 'cleverreach';
  const INTEGRATION_NAME = 'Drupal';
  const SUBSCRIPTION_FIELD = 'field_cleverreach_subscribed';

  const CLEVERREACH_HELP_URL = 'https://support.cleverreach.de/hc/en-us/requests/new';
  const CLEVERREACH_BUILD_EMAIL_URL = '/admin/login.php?ref=%2Fadmin%2Fmailing_create_new.php';

  /**
   * CleveReach API access token.
   *
   * @var string
   */
  private $accessToken;
  /**
   * User info data.
   *
   * @var array
   */
  private $userInfo;
  /**
   * Context data, not used in this integration.
   *
   * @var string
   */
  private $context = '';

  /**
   * @inheritdoc
   */
  public function saveMinLogLevel($minLogLevel) {
    $this->set('min_log_level', $minLogLevel);
  }

  /**
   * @inheritdoc
   */
  public function getMinLogLevel() {
    return $this->get('min_log_level');
  }

  /**
   * @inheritdoc
   */
  public function getAccessToken() {
    if (empty($this->accessToken)) {
      $this->accessToken = $this->get('access_token');
    }

    return $this->accessToken;
  }

  /**
   * @inheritdoc
   */
  public function setAccessToken($accessToken) {
    $this->set('access_token', $accessToken);
    $this->accessToken = $accessToken;
  }

  /**
   * @inheritdoc
   */
  public function isProductSearchEnabled() {
    return TRUE;
  }

  /**
   * Gets site name defined in configuration.
   *
   * @return string
   *   Site name.
   */
  public function getSiteName() {
    return Drupal::configFactory()->get('system.site')->get('name');
  }

  /**
   * @inheritdoc
   */
  public function getProductSearchParameters() {
    $articleSearchUrl = Url::fromRoute('cleverreach.cleverreach.search', [], ['absolute' => TRUE])->toString();

    return [
      'url' => $articleSearchUrl,
      'name' => $this->getIntegrationListName(),
      'password' => $this->getArticleSearchEndpointPassword(),
    ];
  }

  /**
   * @inheritdoc
   */
  public function getIntegrationListName() {
    return "{$this->getIntegrationName()} - {$this->getSiteName()}";
  }

  /**
   * @inheritdoc
   */
  public function getIntegrationName() {
    return self::INTEGRATION_NAME;
  }

  /**
   * @inheritdoc
   */
  public function getIntegrationId() {
    return $this->get('integration_id');
  }

  /**
   * @inheritdoc
   */
  public function setIntegrationId($id) {
    $this->set('integration_id', $id);
  }

  /**
   * @inheritdoc
   */
  public function getUserAccountId() {
    $userInfo = $this->getUserInfo();
    return empty($userInfo['id']) ? '' : $userInfo['id'];
  }

  /**
   * @inheritdoc
   */
  public function setDefaultLoggerEnabled($status) {
    $this->set('default_logger_status', $status);
  }

  /**
   * @inheritdoc
   */
  public function isDefaultLoggerEnabled() {
    return $this->get('default_logger_status') === TRUE;
  }

  /**
   * @inheritdoc
   */
  public function getMaxStartedTasksLimit() {
    return $this->get('max_started_task_limit');
  }

  /**
   * Sets the number of maximum allowed started task at the point in time.
   *
   * @param int $maxStartedTaskLimit
   *   Number of maximum allowed started tasks.
   *
   * @throws ModuleNotInstalledException
   *   When module is not installed.
   */
  public function setMaxStartedTaskLimit($maxStartedTaskLimit) {
    $this->set('max_started_task_limit', $maxStartedTaskLimit);
  }

  /**
   * Get user information.
   *
   * @return array
   *   Keyed array with user info.
   */
  public function getUserInfo() {
    if (empty($this->userInfo)) {
      $this->userInfo = $this->get('user_info');
    }

    return $this->userInfo === NULL ? [] : $this->userInfo;
  }

  /**
   * @inheritdoc
   */
  public function setUserInfo($userInfo) {
    $this->set('user_info', $userInfo);
    $this->userInfo = $userInfo;
  }

  /**
   * @inheritdoc
   */
  public function getTaskRunnerWakeupDelay() {
    return $this->get('task_runner_wakeup_delay');
  }

  /**
   * Set automatic task runner wakeup delay in seconds. Task runner will sleep
   * at the end of its lifecycle for this value seconds before it sends wakeup
   * signal for a new lifecycle. Return null to use default system value (10).
   *
   * @param int $taskRunnerWakeUpDelay
   *   Wakeup delay in seconds.
   *
   * @throws ModuleNotInstalledException
   *   When module is not installed.
   */
  public function setTaskRunnerWakeUpDelay($taskRunnerWakeUpDelay) {
    $this->set('task_runner_wakeup_delay', $taskRunnerWakeUpDelay);
  }

  /**
   * @inheritdoc
   */
  public function getTaskRunnerMaxAliveTime() {
    return $this->get('max_alive_time');
  }

  /**
   * Set maximal time in seconds allowed for runner instance to stay in alive
   * (running) status.
   *
   * @param int $taskRunnerMaxAliveTime
   *   Max execution time of runner in seconds.
   *
   * @throws ModuleNotInstalledException
   *   When module is not installed.
   */
  public function setTaskRunnerMaxAliveTime($taskRunnerMaxAliveTime) {
    $this->set('max_alive_time', $taskRunnerMaxAliveTime);
  }

  /**
   * @inheritdoc
   */
  public function getMaxTaskExecutionRetries() {
    return $this->get('max_task_execution_retries');
  }

  /**
   * Sets maximum number of failed task execution retries.
   *
   * @param int $maxTaskExecutionRetries
   *   Number of task retries  before it is considered as failed.
   *
   * @throws ModuleNotInstalledException
   *   When module is not installed.
   */
  public function setMaxTaskExecutionRetries($maxTaskExecutionRetries) {
    $this->set('max_task_execution_retries', $maxTaskExecutionRetries);
  }

  /**
   * @inheritdoc
   */
  public function getMaxTaskInactivityPeriod() {
    return $this->get('max_task_inactivity_period');
  }

  /**
   * Sets max inactivity period for a task in seconds.
   *
   * @param int $maxTaskInactivityPeriod
   *   Max inactivity period or runner in seconds.
   *
   * @throws ModuleNotInstalledException
   *   When module is not installed.
   */
  public function setMaxTaskInactivityPeriod($maxTaskInactivityPeriod) {
    $this->set('max_task_inactivity_period', $maxTaskInactivityPeriod);
  }

  /**
   * @inheritdoc
   */
  public function getTaskRunnerStatus() {
    $runnerStatus = $this->get('task_runner_status');
    return $runnerStatus === NULL ? [] : $runnerStatus;
  }

  /**
   * @inheritdoc
   */
  public function setTaskRunnerStatus($guid, $timestamp) {
    $this->set('task_runner_status.guid', $guid);
    $this->set('task_runner_status.timestamp', $timestamp);
  }

  /**
   * @inheritdoc
   */
  public function getRecipientsSynchronizationBatchSize() {
    return $this->get('recipient_sync_batch_size');
  }

  /**
   * @inheritdoc
   */
  public function setRecipientsSynchronizationBatchSize($batchSize) {
    $this->set('recipient_sync_batch_size', $batchSize);
  }

  /**
   * @inheritdoc
   */
  public function getClientId() {
    return self::CLIENT_ID;
  }

  /**
   * @inheritdoc
   */
  public function getClientSecret() {
    return self::CLIENT_SECRET;
  }

  /**
   * @inheritdoc
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * @inheritdoc
   */
  public function setContext($context) {
    $this->context = $context;
  }

  /**
   * Return true if recipient is active by default, otherwise false.
   *
   * @return bool
   *   Default subscription behavior.
   */
  public function getDefaultRecipientStatus() {
    return $this->get('default_recipient_status') === TRUE;
  }

  /**
   * Sets default recipient status.
   *
   * @param bool $status
   *   Default subscription behavior.
   *
   * @throws ModuleNotInstalledException
   */
  public function setDefaultRecipientStatus($status) {
    $this->set('default_recipient_status', $status);
  }

  /**
   * Checks if initial sync is configured.
   *
   * @return bool
   *   If initial sync is configured, returns true, otherwise false.
   */
  public function isConfiguredInitialSync() {
    return $this->get('configured_initial_sync') === TRUE;
  }

  /**
   * Sets flag that indicate whether initial sync is configured or not.
   *
   * @param bool $value
   *   Indicator whether initial sync is configured or not.
   *
   * @throws ModuleNotInstalledException
   *   When module is not installed.
   */
  public function setConfiguredInitialSync($value = TRUE) {
    $this->set('configured_initial_sync', $value);
  }

  /**
   * Return whether first email is already built or not.
   *
   * @return bool
   *   When first email is built, return true, otherwise false.
   */
  public function isFirstEmailBuilt() {
    return $this->get('first_email_built') === TRUE;
  }

  /**
   * Sets information whether first email is built.
   *
   * @param bool $value
   *   Indicator whether first email is build or not.
   *
   * @throws ModuleNotInstalledException
   *   When module is not installed.
   */
  public function setIsFirstEmailBuilt($value) {
    $this->set('first_email_built', $value);
  }

  /**
   * Gets article search endpoint password.
   *
   * @return string
   *   Unique article search password stored in database
   *
   * @throws ModuleNotInstalledException
   */
  public function getArticleSearchEndpointPassword() {
    $password = $this->get('article_search_password');

    if ($password === NULL) {
      $password = md5(time());
      $this->setArticleSearchEndpointPassword($password);
    }

    return $password;
  }

  /**
   * Sets article search endpoint password.
   *
   * @param $password
   *
   * @throws ModuleNotInstalledException
   *   When module is not installed.
   */
  public function setArticleSearchEndpointPassword($password) {
    $this->set('article_search_password', $password);
  }

  /**
   * Gets queue name used for this integration.
   *
   * @return string
   *   Queue name used for integration.
   */
  public function getQueueName() {
    return 'drupalDefault';
  }

  /**
   * Sets key-value pair to Drupal configuration table.
   *
   * @param string $key
   *   Identifier to store value in configuration.
   * @param mixed $value
   *   Value to associate with identifier.
   *
   * @throws ModuleNotInstalledException
   */
  private function set($key, $value) {
    if (!$this->get('installed')) {
      // Delete all configuration when module is disabled by uninstall script.
      Drupal::configFactory()->getEditable('cleverreach.settings')->delete();
      // Kill all processes as soon as module uninstall is detected.
      throw new ModuleNotInstalledException('CleverReach module is not currently installed, abort all processes.');
    }

    Drupal::configFactory()->getEditable('cleverreach.settings')->set($key, $value)->save();
  }

  /**
   * Gets configuration by key from Drupal configuration table.
   *
   * @param string $key
   *   Identifier to store value in configuration.
   *
   * @return mixed|null
   *   Configuration value.
   */
  private function get($key) {
    return Drupal::config('cleverreach.settings')->get($key);
  }

}
