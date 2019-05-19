<?php

namespace Drupal\taxonomy_scheduler\Plugin\QueueWorker;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy_scheduler\ValueObject\TaxonomySchedulerQueueItem;

/**
 * Processes nodes with changed topic.
 *
 * @QueueWorker(
 *   id = "taxonomy_scheduler",
 *   title = @Translation("Taxonomy scheduler queue"),
 *   cron = {"time" = 300}
 * )
 */
class TaxonomySchedulerQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * TermStorage.
   *
   * @var \Drupal\taxonomy\TermStorage
   */
  private $termStorage;

  /**
   * DateTime.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  private $dateTime;

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * TaxonomySchedulerQueueWorker constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\taxonomy\TermStorageInterface $termStorage
   *   The term storage.
   * @param \Drupal\Component\Datetime\TimeInterface $dateTime
   *   The time interface.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The config.
   */
  public function __construct(
    array $configuration,
    string $pluginId,
    array $pluginDefinition,
    TermStorageInterface $termStorage,
    TimeInterface $dateTime,
    ImmutableConfig $config
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->termStorage = $termStorage;
    $this->dateTime = $dateTime;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $pluginId,
    $pluginDefinition
  ) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('taxonomy_scheduler.term_storage.factory'),
      $container->get('datetime.time'),
      $container->get('taxonomy_scheduler.config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!$data instanceof TaxonomySchedulerQueueItem) {
      return;
    }

    $term = $this->termStorage->load($data->getTermId());

    if (!$term instanceof TermInterface) {
      return;
    }

    $fieldName = $this->config->get('field_name');

    if (!$term->hasField($fieldName)) {
      return;
    }

    if ($term->get($fieldName)->isEmpty()) {
      return;
    }

    $fieldValue = $term->get($fieldName);

    if (!isset($fieldValue->date)) {
      return;
    }

    $date = $fieldValue->date;

    if (!$date instanceof DrupalDateTime) {
      return;
    }

    $currentTime = $this->dateTime->getCurrentTime();

    if ($date->getTimestamp() <= $currentTime && !$term->isPublished()) {
      $term->save();
    }
  }

}
