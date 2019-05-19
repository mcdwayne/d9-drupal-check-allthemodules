<?php

namespace Drupal\update_runner;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\update\UpdateManagerInterface;
use Drupal\update_runner\Plugin\UpdateRunnerProcessorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates drupal update information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in UpdateOperations.
 *
 * @internal
 */
class UpdateInfo implements ContainerInjectionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * UpdateInfo constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\update_runner\Plugin\UpdateRunnerProcessorPluginManager $pluginManager
   *   Plugin Manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, UpdateRunnerProcessorPluginManager $pluginManager, ConfigFactory $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManager = $pluginManager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.update_runner_processor_plugin'),
      $container->get('config.factory')
    );
  }

  /**
   * Alter the information about available updates for projects.
   *
   * @param array $projects
   *   Reference to an array of information about available updates to each
   *   project installed on the system.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see hook_update_status_alter()
   */
  public function updateStatusAlter(array &$projects) {
    $updates = [];

    // Loop through projects to be updated.
    if (!empty($projects)) {
      foreach ($projects as $projectId => $project) {
        if (in_array($project['status'], [
          UpdateManagerInterface::NOT_CURRENT,
          UpdateManagerInterface::NOT_SECURE,
        ])) {
          $updates[$projectId]['name'] = $project['name'];
          $updates[$projectId]['info'] = $project['info'];
          $updates[$projectId]['existing_version'] = $project['existing_version'];
          $updates[$projectId]['recommended'] = $project['recommended'];
        }
      }
    }

    $processors = $this->entityTypeManager
      ->getStorage("update_runner_processor")
      ->getQuery()
      ->execute();

    if (!empty($updates) && !empty($processors)) {
      foreach ($processors as $id) {

        $md5 = md5(serialize($updates));

        // Do not insert if already created.
        $ids = $this->entityTypeManager
          ->getStorage('update_runner_job')
          ->getQuery()
          ->condition('processor', $id)
          ->condition('hash', $md5)
          ->execute();

        if (!empty($ids)) {
          continue;
        }

        $values = [
          'hash' => $md5,
          'status' => UPDATE_RUNNER_JOB_NOT_PROCESSED,
          'created' => time(),
          'data' => serialize($updates),
          'processor' => $id,
        ];

        $processorConfig = $this->configFactory->get('update_runner.update_runner_processor.' . $id);
        $pluginType = $processorConfig->get('plugin');

        // Insert job.
        $this->pluginManager->createInstance($pluginType, [])->insert($values);
      }
    }
  }

}
