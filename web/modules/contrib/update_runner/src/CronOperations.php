<?php

namespace Drupal\update_runner;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to cron events.
 *
 * @internal
 */
class CronOperations implements ContainerInjectionInterface {

  /**
   * The update runner manager service.
   *
   * @var \Drupal\update_runner\UpdateRunnerManager
   */
  protected $updateRunnerManager;

  /**
   * CronOperations constructor.
   *
   * @param \Drupal\update_runner\UpdateRunnerManager $update_runner_manager
   *   The update runner manager service.
   */
  public function __construct(UpdateRunnerManager $update_runner_manager) {
    $this->updateRunnerManager = $update_runner_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('update_runner.manager')
    );
  }

  /**
   * Perform periodic actions.
   *
   * @see hook_cron()
   */
  public function cron() {
    $this->updateRunnerManager->process();
  }

}
