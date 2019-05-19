<?php

namespace Drupal\taxonomy_scheduler\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Queue\QueueInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\taxonomy\TermStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\taxonomy_scheduler\ValueObject\TaxonomySchedulerQueueItem;

/**
 * Class TaxonomySchedulerCronSubscriber.
 */
class TaxonomySchedulerCronSubscriber implements EventSubscriberInterface {

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * TermStorage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  private $termStorage;

  /**
   * Queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  private $queue;

  /**
   * DateTime.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  private $dateTime;

  /**
   * DateFormatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private $dateFormatter;

  /**
   * TaxonomySchedulerCronSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The config.
   * @param \Drupal\taxonomy\TermStorageInterface $termStorage
   *   The term storage.
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The queue.
   * @param \Drupal\Component\Datetime\TimeInterface $dateTime
   *   The datetime object.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   */
  public function __construct(
    ImmutableConfig $config,
    TermStorageInterface $termStorage,
    QueueInterface $queue,
    TimeInterface $dateTime,
    DateFormatterInterface $dateFormatter
  ) {
    $this->config = $config;
    $this->termStorage = $termStorage;
    $this->queue = $queue;
    $this->dateTime = $dateTime;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * Add items to the queue for processing.
   */
  public function taxonomySchedulerCron(): void {
    $vocabularies = $this->config->get('vocabularies');
    $fieldName = $this->config->get('field_name');
    $currentISOTime = $this->dateFormatter->format($this->dateTime->getCurrentTime(), 'custom', 'c');

    foreach ($vocabularies as $vocabulary) {
      $query = $this->termStorage->getQuery();
      $termIds = $query->condition('vid', $vocabulary)
        ->condition($fieldName, '', '!=')
        ->condition($fieldName, $currentISOTime, '<=')
        ->condition('status', 0, '=')
        ->execute();

      foreach ($termIds as $termId) {
        $data = new TaxonomySchedulerQueueItem(['termId' => $termId]);
        $this->queue->createItem($data);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      HookEventDispatcherInterface::CRON => 'taxonomySchedulerCron',
    ];
  }

}
