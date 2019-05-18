<?php

namespace Drupal\daemons\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\daemons\DaemonManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides default daemon controller.
 *
 * @package Drupal\daemons\Controller
 */
class DaemonController extends ControllerBase {

  protected $daemonService;

  /**
   * {@inheritdoc}
   */
  public function __construct(DaemonManager $daemonService) {
    $this->daemonService = $daemonService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $daemonService = $container->get('daemon.manager');

    return new static($daemonService);
  }

  /**
   * Execute task for daemon.
   */
  public function run(string $task, $daemon) {
    $this->daemonService->daemonExecute($task, $daemon);

    return $this->redirect('daemons.list');
  }

}
