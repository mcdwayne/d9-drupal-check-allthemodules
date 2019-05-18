<?php

namespace CleverReach\Infrastructure\Interfaces\Required;

/**
 *
 */
interface Configuration {

  const CLASS_NAME = __CLASS__;

  /**
   * Sets task execution context.
   *
   * When integration supports multiple accounts (middleware integration) proper context must be set based on middleware account
   * that is using core library functionality. This context should then be used by business services to fetch account specific
   * data.Core will set context provided upon task enqueueing before task execution.
   *
   * @param string $context
   *   Context to set.
   */
  public function setContext($context);

  /**
   * Gets task execution context.
   *
   * @return string Context in which task is being executed. If no context is provided empty string is returned (global context)
   */
  public function getContext();

  /**
   * Saves min log level in integration database.
   *
   * @param int $minLogLevel
   */
  public function saveMinLogLevel($minLogLevel);

  /**
   * Retrieves min log level from integration database.
   *
   * @return int
   */
  public function getMinLogLevel();

  /**
   * Retrieves access token from integration database.
   *
   * @return string
   */
  public function getAccessToken();

  /**
   * Save access token in integration database.
   *
   * @param string $accessToken
   */
  public function setAccessToken($accessToken);

  /**
   * Save user information in integration database.
   *
   * @param array $userInfo
   */
  public function setUserInfo($userInfo);

  /**
   * Return whether product search is enabled or not.
   *
   * @return bool
   */
  public function isProductSearchEnabled();

  /**
   * Retrieves parameters needed for product search registrations.
   *
   * @return array, with array keys name, url, password
   */
  public function getProductSearchParameters();

  /**
   * Retrieves integration name.
   *
   * @return string
   */
  public function getIntegrationName();

  /**
   * Retrieves name of the CleverReach list (group)
   *
   * @return string
   */
  public function getIntegrationListName();

  /**
   * Retrieves integration id.
   *
   * @return int
   */
  public function getIntegrationId();

  /**
   * Saves created groupId in CR to integration.
   *
   * @param int $id
   */
  public function setIntegrationId($id);

  /**
   * Retrieves user account id.
   *
   * @return string
   */
  public function getUserAccountId();

  /**
   * Set default logger status (enabled/disabled)
   *
   * @param bool $status
   */
  public function setDefaultLoggerEnabled($status);

  /**
   * Return whether default logger is enabled or not.
   *
   * @return bool
   */
  public function isDefaultLoggerEnabled();

  /**
   * Gets the number of maximum allowed started task at the point in time. This number will determine how many tasks can be
   * in "in_progress" status at the same time.
   *
   * @return int
   */
  public function getMaxStartedTasksLimit();

  /**
   * Automatic task runner wakeup delay in seconds. Task runner will sleep at the end of its lifecycle for this value seconds
   * before it sends wakeup signal for a new lifecycle. Return null to use default system value (10)
   *
   * @return int|null
   */
  public function getTaskRunnerWakeupDelay();

  /**
   * Gets maximal time in seconds allowed for runner instance to stay in alive (running) status. After this period system will
   * automatically start new runner instance and shutdown old one. Return null to use default system value (60)
   *
   * @return int|null
   */
  public function getTaskRunnerMaxAliveTime();

  /**
   * Gets maximum number of failed task execution retries. System will retry task execution in case of error until this number
   * is reached. Return null to use default system value (5)
   *
   * @return int|null
   */
  public function getMaxTaskExecutionRetries();

  /**
   * Gets max inactivity period for a task in seconds. After inactivity period is passed, system will fail such tasks as expired.
   * Return null to use default system value (30)
   *
   * @return int|null
   */
  public function getMaxTaskInactivityPeriod();

  /**
   * Gets batch size for synchronization set in configuration.
   *
   * @return int
   */
  public function getRecipientsSynchronizationBatchSize();

  /**
   * Sets synchronization batch size.
   *
   * @param int $batchSize
   */
  public function setRecipientsSynchronizationBatchSize($batchSize);

  /**
   * Gets client id.
   *
   * @return string
   */
  public function getClientId();

  /**
   * Gets client secret.
   *
   * @return string
   */
  public function getClientSecret();

  /**
   * @return array
   */
  public function getTaskRunnerStatus();

  /**
   * Sets task runner status information as JSON encoded string.
   *
   * @param string $guid
   * @param int $timestamp
   *
   * @throws TaskRunnerStatusStorageUnavailableException
   */
  public function setTaskRunnerStatus($guid, $timestamp);

}
