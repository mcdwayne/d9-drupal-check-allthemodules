<?php

namespace Drupal\daemons\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\daemons\PluginDaemonManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\daemons\DaemonManager;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Provide list of daemons.
 *
 * @package Drupal\daemons\Controller
 */
class DaemonsListController extends ControllerBase {

  /**
   * The Daemon plugin manager.
   *
   * @var \Drupal\daemons\PluginDaemonManager
   */
  protected $daemonPluginManager;

  /**
   * The Daemon manager.
   *
   * @var \Drupal\daemons\DaemonManager
   */
  protected $daemonManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Creates a new MenuController object.
   *
   * @param \Drupal\daemons\PluginDaemonManager $daemon_plugin_manager
   *   The Daemon plugin manager service.
   * @param \Drupal\daemons\DaemonManager $daemon_manager
   *   The Daemon manager service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter manager service.
   */
  public function __construct(PluginDaemonManager $daemon_plugin_manager, DaemonManager $daemon_manager, DateFormatterInterface $date_formatter) {
    $this->daemonPluginManager = $daemon_plugin_manager;
    $this->daemonManager = $daemon_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.daemon'),
      $container->get('daemon.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * List daemons.
   */
  public function daemonsList() {
    $list = [];
    $list['#type'] = 'container';
    $list['daemons'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => $this->buildRows(),
      '#empty' => '',
      '#attributes' => [
        'id' => 'daemons-list',
        'class' => ['daemons-list'],
      ],
    ];

    return $list;
  }

  /**
   * Build header.
   */
  protected function buildHeader() {
    return [
      $this->t('Name'),
      $this->t('Status'),
      $this->t('Pid'),
      $this->t('Last run'),
      $this->t('Operations'),
    ];
  }

  /**
   * Prepare list of daemons.
   */
  protected function buildRows() {
    $rows = [];
    // Get all existing daemon plugin.
    $plugin_service = $this->daemonPluginManager;
    foreach ($plugin_service->getDefinitions() as $plugin_id => $plugin) {
      $instance = $plugin_service->createInstance($plugin_id);

      // Get stored daemons data.
      $data = $this
        ->daemonManager
        ->getDaemonData($plugin_id);
      // Convert last run date with short format.
      $date = '-';
      if (!empty($data['lastRunTime'])) {
        $date = $this
          ->dateFormatter
          ->format($data['lastRunTime'], 'short');
      }

      // Row data.
      // Daemon plugin name.
      $row['title']['data']['#markup'] = $instance->getLabel();
      // Status running of daemon.
      $row['status']['data']['#markup'] = $instance->getStatus();
      // Daemon process id.
      $row['pid']['data']['#markup'] = $data['processId'];
      // Date of last running.
      $row['last_run']['data']['#markup'] = $date;

      // Build operations for daemon.
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => [
          'daemon_start' => [
            'title' => $this->t('Start'),
            'url' => Url::fromRoute('daemon.run', [
              'task' => 'start',
              'daemon' => $instance->getId(),
            ]),
          ],
          'daemon_stop' => [
            'title' => $this->t('Stop'),
            'url' => Url::fromRoute('daemon.run', [
              'task' => 'stop',
              'daemon' => $instance->getId(),
            ]),
          ],
          'daemon_force_stop' => [
            'title' => $this->t('Force stop'),
            'url' => Url::fromRoute('daemon.run', [
              'task' => 'forceStop',
              'daemon' => $instance->getId(),
            ]),
          ],
          'daemon_restart' => [
            'title' => $this->t('Restart'),
            'url' => Url::fromRoute('daemon.run', [
              'task' => 'restart',
              'daemon' => $instance->getId(),
            ]),
          ],
        ],
      ];

      $rows[] = $row;
    }

    return $rows;
  }

}
