<?php

namespace Drupal\centreon_status\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\centreon_status\Service\CentreonStatus;

/**
 * Provides route responses for the Example module.
 */
class ServicesController extends ControllerBase {

  /**
   * The check provider.
   *
   * @var \Drupal\centreon_status\Service\CentreonStatus
   */
  protected $centreonstatus;

  /**
   * HostsController constructor.
   *
   * @param \Drupal\centreon_status\Service\CentreonStatus $centreonStatus
   *   CentreonStatus.
   */
  public function __construct(CentreonStatus $centreonStatus) {
    $this->centreonstatus = $centreonStatus;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('centreon_status.centreon_status')
    );
  }

  /**
   * Get Content.
   *
   * @return array
   *   renderable array.
   */
  public function getContent() {
    $header = [
      ['data' => $this->t('State')],
      ['data' => $this->t('Hostname')],
      ['data' => $this->t('Services')],
      ['data' => $this->t('Status information')],
    ];

    // Populate the rows.
    $rows = [];
    foreach ($this->centreonstatus->getRealtime('services') as $row) {

      switch ($row->state) {
        case 0:
          $current_state = "OK";
          $class = "status_ok";
          break;

        case 1:
          $current_state = "Warning";
          $class = "status_warning";
          break;

        case 2:
          $current_state = "Critical";
          $class = "status_critical";
          break;

        case 3:
          $current_state = "Unknown";
          $class = "status_unknown";
          break;
      }

      $rows[] = [
        'data' => [
          [
            'data' => [
              '#markup' => '<span class="' . $class . ' badge_cenreon">' . $current_state . '</span>',
            ],
            'class' => 'tdcenter',
          ],
          'hostname' => $row->name,
          'service' => $row->description,
          'status information' => $row->output,
        ],
      ];
    }

    // Generate the table.
    $build['config_table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => [
        'id' => ['centreon_status'],
      ],
      '#attached' => [
        'library' => [
          'centreon_status/centreon_status',
        ],
      ],
    ];
    // Finally add the pager.
    $build['pager'] = [
      '#type' => 'pager',
    ];
    \Drupal::service('page_cache_kill_switch')->trigger();
    return $build;
  }

}
