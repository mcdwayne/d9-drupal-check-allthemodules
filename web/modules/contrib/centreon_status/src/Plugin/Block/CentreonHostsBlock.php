<?php

namespace Drupal\centreon_status\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\centreon_status\Service\CentreonStatus;

/**
 * Provides a 'centreon_status' block.
 *
 * @Block(
 *  id = "centreon_hosts_status",
 *  admin_label = @Translation("Hosts status"),
 * )
 */
class CentreonHostsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * CentreonStatus service.
   *
   * @var \Drupal\centreon_status\Service\CentreonStatus
   */
  protected $centreonstatus;

  /**
   * CentreonServicesBlock constructor.
   *
   * @param array $configuration
   *   Array of configurations.
   * @param string $plugin_id
   *   Plugin id value.
   * @param mixed $plugin_definition
   *   Plugin definition value.
   * @param \Drupal\centreon_status\Service\CentreonStatus $centreonStatus
   *   CentreonStatus.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CentreonStatus $centreonStatus) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->centreonstatus = $centreonStatus;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('centreon_status.centreon_status')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $header = [
        ['data' => $this->t('State')],
        ['data' => $this->t('Hostname')],
    ];

    // Populate the rows.
    $rows = [];
    foreach ($this->centreonstatus->getRealtime('hosts') as $row) {
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
          '#cache' => [
            'max_age' => 300,
          ],
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
    ];

    // Finally add the pager.
    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

}
