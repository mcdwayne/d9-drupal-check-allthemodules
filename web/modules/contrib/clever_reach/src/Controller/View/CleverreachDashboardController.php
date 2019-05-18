<?php

namespace Drupal\clever_reach\Controller\View;

use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\QueueItem;
use Drupal\clever_reach\Component\Infrastructure\ConfigService;

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
   *
   * @throws \Exception
   */
  public function content() {
    $this->dispatch();
    $userInfo = $this->getConfigService()->getUserInfo();
    $failureParameters = $this->getInitialSyncFailureParameters();
    $viewParams = [
      '#recipient_id' => $userInfo['id'],
      '#integration_name' => $this->getConfigService()->getIntegrationName(),
      '#is_built_first_email' => $this->getConfigService()->isFirstEmailBuilt(),
      '#is_initial_sync_failed' => $failureParameters['is_failed'],
      '#initial_sync_failed_message' => $failureParameters['message'],
      '#urls' => [
        'logo_url' => $this->getThemePath('images/icon_quickstartmailing.svg'),
        'dashboard_logo_url' => $this->getThemePath('images/cr_logo_transparent_107px.png'),
        'help_url' => ConfigService::CLEVERREACH_HELP_URL,
        'built_email_url' => 'https://' . $userInfo['login_domain'] . ConfigService::CLEVERREACH_BUILD_EMAIL_URL,
        'built_first_email_url' => $this->getControllerUrl('build.first.email'),
        'retry_sync_url' => $this->getControllerUrl('retry.sync'),
      ],
    ];

    if (!$failureParameters['is_failed']) {
      $viewParams['#report'] = $this->getInitialSyncReport();
    }

    $theme = [
      '#theme' => self::TEMPLATE,
      '#attached' => [
        'library' => ['clever_reach/cleverreach-dashboard-view'],
      ],
    ];

    return array_merge($viewParams, $theme);
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

  /**
   * Generates initial sync report.
   *
   * @return array
   *   Array with initial sync report.
   *
   * @throws \Exception
   */
  private function getInitialSyncReport() {
    $configService = $this->getConfigService();
    $result = [
      'isReportEnabled' => !$configService->isImportStatisticsDisplayed(),
    ];

    if ($result['isReportEnabled']) {
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $result['recipients'] = number_format(
        $configService->getNumberOfSyncedRecipients(),
        0,
        $language === 'en' ? '.' : ',',
        $language === 'en' ? ',' : '.'
      );
      $result['name'] = $configService->getIntegrationListName();
      $result['tags'] = $this->getFormattedTags();
      $configService->setImportStatisticsDisplayed(TRUE);
    }

    return $result;
  }

  /**
   * Generates formatted tags.
   *
   * @return array
   *   Array of formatted tags.
   */
  private function getFormattedTags() {
    $result = [];
    $recipientsService = ServiceRegister::getService(Recipients::CLASS_NAME);
    $tags = $recipientsService->getAllTags()->toArray();
    $numberOfTags = \count($tags);
    if ($numberOfTags === 0) {
      return $result;
    }

    $trimmedTags = array_slice($tags, 0, 3);
    /** @var \CleverReach\BusinessLogic\Entity\Tag $tag */
    foreach ($trimmedTags as $index => $tag) {
      $result[] = '<div class="value" title="' . $tag->getTitle() . '">'
                    . $this->getTrimmedTagName($index + 1 . ') ' . $tag->getTitle())
                . '</div>';
    }

    if ($numberOfTags > 3) {
      $result[] = '<div class="value">...</div>';
    }

    return $result;
  }

  /**
   * Trims tag name to specified length.
   *
   * @param string $tag
   *   Tag to get trimmed name.
   * @param int $maxChars
   *   Max length of trimmed name.
   * @param string $filler
   *   Trim filler.
   *
   * @return string
   *   Trimmed tag.
   */
  private function getTrimmedTagName($tag, $maxChars = 24, $filler = '...') {
    $length = \strlen($tag);
    $filterLength = \strlen($filler);

    return $length > $maxChars ? substr_replace(
      $tag,
      $filler,
      $maxChars - $filterLength,
      $length - $maxChars
    ) : $tag;
  }

}
