<?php

namespace Drupal\cleverreach\Controller\View;

use CleverReach\Infrastructure\TaskExecution\QueueItem;
use Drupal\cleverreach\Component\Infrastructure\ConfigService;

/**
 * Dashboard View Controller.
 *
 * @see template file cleverreach-dashboard.html.twig
 */
class CleverreachDashboardController extends CleverreachResolveStateController {
  const CURRENT_STATE_CODE = 'dashboard';
  const TEMPLATE = 'cleverreach_dashboard';

  /**
   * Callback for the cleverreach.cleverreach.welcome route.
   *
   * @return array
   *   Template variables.
   */
  public function content() {
    $this->dispatch();
    $userInfo = $this->getConfigService()->getUserInfo();
    $failureParameters = $this->getInitialSyncFailureParameters();

    return [
      '#recipient_id' => $userInfo['id'],
      '#integration_name' => $this->getConfigService()->getIntegrationName(),
      '#is_built_first_email' => $this->getConfigService()->isFirstEmailBuilt(),
      '#is_initial_sync_failed' => $failureParameters['is_failed'],
      '#initial_sync_failed_message' => $failureParameters['message'],
      '#urls' => [
        'logo_url' => $this->getThemePath('images/icon_quickstartmailing.svg'),
        'help_url' => ConfigService::CLEVERREACH_HELP_URL,
        'built_email_url' => 'https://' . $userInfo['login_domain'] . ConfigService::CLEVERREACH_BUILD_EMAIL_URL,
        'built_first_email_url' => $this->getControllerUrl('build.first.email'),
        'retry_sync_url' => $this->getControllerUrl('retry.sync'),
      ],
      '#theme' => self::TEMPLATE,
      '#attached' => [
        'library' => ['cleverreach/cleverreach-dashboard-view'],
      ],
    ];
  }

  /**
   * Gets fail parameters for initial sync task if it failed.
   *
   * @return array
   *   Returns initial sync status array.
   *   Example: ['is_failed' => TRUE, 'message' => ''].
   */
  private function getInitialSyncFailureParameters() {
    $params = ['is_failed' => FALSE, 'message' => ''];

    /** @var \CleverReach\Infrastructure\TaskExecution\QueueItem $initialSyncTask */
    $initialSyncTask = $this->getQueueService()->findLatestByType('InitialSyncTask');
    if ($initialSyncTask && $initialSyncTask->getStatus() === QueueItem::FAILED) {
      $params = [
        'is_failed' => TRUE,
        'message' => $initialSyncTask->getFailureDescription(),
      ];
    }

    return $params;
  }

}
