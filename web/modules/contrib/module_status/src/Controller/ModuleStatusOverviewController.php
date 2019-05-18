<?php

namespace Drupal\module_status\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\module_status\RssIssueReaderServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ModuleStatusOverviewController.
 */
class ModuleStatusOverviewController extends ControllerBase {

  /**
   * @var \Drupal\module_status\RssIssueReaderService
   */
  private $rssIssueReader;

  /**
   * @param RssIssueReaderServiceInterface $rssIssueReaderService
   */
  public function __construct(
    RssIssueReaderServiceInterface $rssIssueReaderService
  ) {
    $this->rssIssueReader = $rssIssueReaderService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_status.rss_issue_reader')
    );
  }

  /**
   * Render overview.
   *
   * @return array
   *   A render array.
   */
  public function renderOverview() {
    return [
      [
        '#theme' => 'module_status_overview',
        '#last_update_label' => $this->t('Last update: '),
        '#last_update_date' => DrupalDateTime::createFromTimestamp(time())
          ->format(DrupalDateTime::RFC7231),
        '#refresh_link' => [
          'text' => $this->t('Refresh now'),
          'path' => 'module_status.module_status_overview_controller_renderOverview',
        ],
        '#module_status_overview_table' => [
          '#theme' => 'table',
          '#cache' => ['disable' => TRUE],
          //          '#caption' => $this->t('Installed modules'),
          '#header' => [
            $this->t('Module Name'),
            $this->t('Number of known critical Issues'),
            $this->t('Issue Page'),
          ],
        ],
        '#module_status_overview_data' => $this->rssIssueReader->getModuleIssues(),
      ],
    ];
  }

}
