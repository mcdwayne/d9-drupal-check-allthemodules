<?php

namespace Drupal\clever_reach\Component\Infrastructure;

use CleverReach\Infrastructure\Interfaces\Required\Configuration as ConfigInterface;
use Drupal;
use Drupal\Core\Url;

/**
 * Configuration service.
 *
 * @see \CleverReach\Infrastructure\Interfaces\Required\Configuration
 */
class ConfigService extends ConfigInterface {
  const CLIENT_ID = 'nRiRL1I0WF';
  const CLIENT_SECRET = 'tdOshpUD6LvSq9h0RiXklkcDcYFUYCAr';

  const MODULE_NAME = 'clever_reach';
  const INTEGRATION_NAME = 'Drupal';
  const SUBSCRIPTION_FIELD = 'field_cleverreach_subscribed';

  const CLEVERREACH_HELP_URL = 'https://support.cleverreach.de/hc/en-us/requests/new';
  const CLEVERREACH_BUILD_EMAIL_URL = '/admin/login.php?ref=%2Fadmin%2Fmailing_create_new.php';

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   *
   * @throws \Drupal\clever_reach\Exception\ModuleNotInstalledException
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
   * {@inheritdoc}
   */
  public function getIntegrationListName() {
    return "{$this->getIntegrationName()} - {$this->getSiteName()}";
  }

  /**
   * {@inheritdoc}
   */
  public function getIntegrationName() {
    return self::INTEGRATION_NAME;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserInfo() {
    if (empty($this->userInfo)) {
      $this->userInfo = $this->getConfigRepository()->get('CLEVERREACH_USER_INFO');
    }

    return $this->userInfo === NULL ? [] : $this->userInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserInfo($userInfo) {
    $this->getConfigRepository()->set('CLEVERREACH_USER_INFO', $userInfo);
    $this->userInfo = $userInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function getTaskRunnerStatus() {
    $runnerStatus = $this->getConfigRepository()->get('CLEVERREACH_TASK_RUNNER_STATUS');

    return $runnerStatus === NULL ? [] : $runnerStatus;
  }

  /**
   * {@inheritdoc}
   */
  public function setTaskRunnerStatus($guid, $timestamp) {
    $this->getConfigRepository()->set('CLEVERREACH_TASK_RUNNER_STATUS.GUID', $guid);
    $this->getConfigRepository()->set('CLEVERREACH_TASK_RUNNER_STATUS.TIMESTAMP', $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function getClientId() {
    return self::CLIENT_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientSecret() {
    return self::CLIENT_SECRET;
  }

  /**
   * Return true if recipient is active by default, otherwise false.
   *
   * @return bool
   *   Default subscription behavior.
   */
  public function getDefaultRecipientStatus() {
    return $this->getConfigRepository()->get('CLEVERREACH_DEFAULT_RECIPIENT_STATUS') === TRUE;
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
    $this->getConfigRepository()->set('CLEVERREACH_DEFAULT_RECIPIENT_STATUS', $status);
  }

  /**
   * Checks if initial sync is configured.
   *
   * @return bool
   *   If initial sync is configured, returns true, otherwise false.
   */
  public function isConfiguredInitialSync() {
    return $this->getConfigRepository()->get('CLEVERREACH_CONFIGURED_INITIAL_SYNC') === TRUE;
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
    $this->getConfigRepository()->set('CLEVERREACH_CONFIGURED_INITIAL_SYNC', $value);
  }

  /**
   * Gets article search endpoint password.
   *
   * @return string
   *   Unique article search password stored in database
   *
   * @throws \Drupal\clever_reach\Exception\ModuleNotInstalledException
   */
  public function getArticleSearchEndpointPassword() {
    $password = $this->getConfigRepository()->get('CLEVERREACH_ARTICLE_SEARCH_PASSWORD');

    if ($password === NULL) {
      $password = md5(time());
      $this->setArticleSearchEndpointPassword($password);
    }

    return $password;
  }

  /**
   * Sets article search endpoint password.
   *
   * @param string $password
   *   CleverReach article search password.
   *
   * @throws ModuleNotInstalledException
   *   When module is not installed.
   */
  public function setArticleSearchEndpointPassword($password) {
    $this->getConfigRepository()->set('CLEVERREACH_ARTICLE_SEARCH_PASSWORD', $password);
  }

  /**
   * {@inheritdoc}
   */
  public function getQueueName() {
    return 'drupalDefault';
  }

  /**
   * Retrieves URL of a controller that will handle webhook calls.
   *
   * (GET for verification, POST for handling)
   *
   * @return string
   *   Event handler endpoint URL.
   */
  public function getCrEventHandlerURL() {
    return Url::fromRoute('cleverreach.cleverreach.event.handler', [], ['absolute' => TRUE])->toString();
  }

}
