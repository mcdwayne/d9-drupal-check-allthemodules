<?php

/**
 * @file
 * Contains \Drupal\temporal\Plugin\QueueWorker\TemporalFutureWorker.
 */

namespace Drupal\temporal\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\temporal\TemporalService;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Processes future temporal records so they apply to the entity at the right time
 *
 * @QueueWorker(
 *   id = "temporal_future_worker",
 *   title = @Translation("Temporal Future Entry Worker, apply values to entities when the time occurrs"),
 *   cron = {"time" = 10}
 * )
 */
class TemporalFutureWorker extends QueueWorkerBase {
  protected $logger;

  use StringTranslationTrait;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = \Drupal::logger('temporal');

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container);
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($action) {
    /** @var TemporalService $temporal_service */
    $temporal_service = \Drupal::service('temporal');
    $status = $temporal_service->performFutureValueActions();
    $this->logger->notice("Temporal Worker Cron Run Result: %status Items processed", ['%status' => $status]);
  }

}