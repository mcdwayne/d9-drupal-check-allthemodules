<?php

namespace Drupal\clever_reach\Controller\View;

use CleverReach\BusinessLogic\Sync\InitialSyncTask;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\Infrastructure\TaskExecution\Queue;
use CleverReach\Infrastructure\TaskExecution\QueueItem;
use Drupal\clever_reach\Component\Utility\TaskQueue;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Resolver Controller. Resolves current state of user synchronization.
 */
class CleverreachResolveStateController implements ContainerInjectionInterface {
  const TEMPLATE = '';
  const CURRENT_STATE_CODE = '';

  const WELCOME_STATE_CODE = 'welcome';
  const INITIAL_SYNC_CONFIG_STATE_CODE = 'initialsync_config';
  const INITIAL_SYNC_STATE_CODE = 'initialsync';
  const DASHBOARD_STATE_CODE = 'dashboard';
  const TOKEN_EXPIRED_STATE_CODE = 'tokenexpired';

  /**
   * Instance of Queue class.
   *
   * @var \CleverReach\Infrastructure\TaskExecution\Queue
   */
  private $queue;
  /**
   * Instance of Configuration class.
   *
   * @var \CleverReach\Infrastructure\Interfaces\Required\Configuration
   */
  private $configService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Callback for the cleverreach.cleverreach.welcome route.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects user to new url.
   */
  public function dispatch() {
    TaskQueue::wakeup();

    if (!$this->isAuthTokenValid()) {
      return $this->redirect(self::WELCOME_STATE_CODE);
    }

    if (!$this->isSyncConfigured()) {
      return $this->redirect(self::INITIAL_SYNC_CONFIG_STATE_CODE);
    }

    if ($this->isInitialSyncInProgress()) {
      return $this->redirect(self::INITIAL_SYNC_STATE_CODE);
    }

    if (!$this->checkIfTokenIsRefreshed()) {
      return $this->redirect(self::TOKEN_EXPIRED_STATE_CODE);
    }

    return $this->redirect(self::DASHBOARD_STATE_CODE);
  }

  /**
   * Gets resource path.
   *
   * @param string $resourcePath
   *   Resource path relative to theme folder under cleverreach module.
   *
   * @return string
   *   Base path to provided resource.
   */
  protected function getThemePath($resourcePath) {
    return file_create_url(drupal_get_path('module', 'clever_reach') . "/theme/$resourcePath");
  }

  /**
   * Gets full url to the controller by provided route name.
   *
   * @param string $routeName
   *   CleverReach Route name.
   * @param array $params
   *   Query params, in the form of key-value pairs.
   *
   * @return string
   *   Full url to controller.
   *
   * @see cleverreach.routing.yml
   */
  protected function getControllerUrl($routeName, array $params = []) {
    return Url::fromRoute("cleverreach.cleverreach.$routeName", [], [
      'absolute' => TRUE,
      'query' => $params,
    ])
      ->toString();
  }

  /**
   * Gets CleverReach configuration service.
   *
   * @return \Drupal\clever_reach\Component\Infrastructure\ConfigService
   *   New instance of ConfigService.
   */
  protected function getConfigService() {
    if (NULL === $this->configService) {
      $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    return $this->configService;
  }

  /**
   * Gets CleverReach queue service.
   *
   * @return \CleverReach\Infrastructure\TaskExecution\Queue
   *   New instance of Queue.
   */
  protected function getQueueService() {
    if (NULL === $this->queue) {
      $this->queue = ServiceRegister::getService(Queue::CLASS_NAME);
    }

    return $this->queue;
  }

  /**
   * Redirects user to proper state.
   *
   * @param string $routeName
   *   CleverReach Route name.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   Redirect to page.
   */
  private function redirect($routeName) {
    if (static::CURRENT_STATE_CODE === $routeName) {
      return NULL;
    }

    $urlRelativePath = Url::fromRoute("cleverreach.cleverreach.$routeName")->toString();
    $response = new RedirectResponse($urlRelativePath);
    return $response->send();
  }

  /**
   * Checks if user is logged in to CleverReach account.
   *
   * @return bool
   *   If auth token is valid returns true, otherwise false.
   */
  private function isAuthTokenValid() {
    return !empty($this->getConfigService()->getAccessToken());
  }

  /**
   * Checks if user is already configured initial sync.
   *
   * @return bool
   *   If sync configured returns true, otherwise false.
   */
  private function isSyncConfigured() {
    return $this->getConfigService()->isConfiguredInitialSync();
  }

  /**
   * Checks if initial sync is in progress.
   *
   * @return bool
   *   If initial sync is in progress returns true, otherwise false.
   */
  private function isInitialSyncInProgress() {
    /** @var \CleverReach\Infrastructure\TaskExecution\QueueItem $initialSyncTaskItem */
    $initialSyncTaskItem = $this->getQueueService()->findLatestByType('InitialSyncTask');
    if (!$initialSyncTaskItem) {
      try {
        TaskQueue::enqueue(new InitialSyncTask());
      }
      catch (QueueStorageUnavailableException $e) {
        // If task enqueue fails do nothing but report
        // that initial sync is in progress.
      }

      return TRUE;
    }

    return $initialSyncTaskItem->getStatus() !== QueueItem::COMPLETED
      && $initialSyncTaskItem->getStatus() !== QueueItem::FAILED;
  }

  /**
   * Checks if token has been refreshed.
   *
   * @return bool
   *   TRUE if token has been refreshed.
   */
  private function checkIfTokenIsRefreshed() {
    $refreshToken = $this->getConfigService()->getRefreshToken();
    $exchangeTokenTask = $this->getQueueService()->findLatestByType('ExchangeAccessTokenTask');
    return !empty($refreshToken)
      || ($exchangeTokenTask !== NULL
        && !in_array($exchangeTokenTask->getStatus(), [QueueItem::COMPLETED, QueueItem::FAILED], TRUE)
      );
  }

}
