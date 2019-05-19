<?php

namespace Drupal\update_runner;

use Drupal\update_runner\Plugin\UpdateRunnerProcessorPluginManager;
use Drupal\Core\Entity\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Service to manage orchestation of update runner jobs.
 */
class UpdateRunnerManager {

  protected $efq;

  /**
   * UpdateRunnerManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityManager $entityManager
   *   Entity manager.
   * @param \Drupal\update_runner\Plugin\UpdateRunnerProcessorPluginManager $pluginManager
   *   Plugin manager.
   */
  public function __construct(ConfigFactory $config_factory, EntityManager $entityManager, UpdateRunnerProcessorPluginManager $pluginManager) {
    $this->entityStorage = $entityManager->getStorage('update_runner_job');
    $this->pluginManager = $pluginManager;
    $this->configFactory = $config_factory;
  }

  /**
   * Create function for UpdateRunnerManager.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity.manager'),
      $container->get('plugin.manager.update_runner_processor_plugin')
    );
  }

  /**
   * Process pending jobs.
   */
  public function process() {

    // Load all available update runner jobs.
    $ids = $this->entityStorage->getQuery()
      ->condition('status', UPDATE_RUNNER_JOB_NOT_PROCESSED)
      ->execute();

    $availableUpdates = $this->entityStorage->loadMultiple($ids);

    // Executes them.
    foreach ($availableUpdates as $update) {

      $processorConfig = $this->configFactory->get('update_runner.update_runner_processor.' . $update->processor->value);
      $pluginType = $processorConfig->get('plugin');

      $status = $this->pluginManager->createInstance($pluginType, unserialize($processorConfig->get('data')))->run($update);
      $update->status = $status;
      $update->save();
    }
  }

}
