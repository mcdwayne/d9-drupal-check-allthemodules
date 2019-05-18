<?php

namespace Drupal\interface_string_stats\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * Class StringStatsExit.
 */
class StringStatsExit implements EventSubscriberInterface {

  /**
   * QueueFactory object.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * Constructs a StringStatsExit object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The QueueFactory object.
   */
  public function __construct(QueueFactory $queueFactory) {
    $this->queue = $queueFactory->get('interface_string_stats');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = ['terminate', 200];

    return $events;
  }

  /**
   * Load strings from the static cache and pass to the queue to process fully.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The PostResponseEvent object.
   */
  public function terminate(PostResponseEvent $event) {
    $requested_string_translations = &drupal_static('interface_string_stats_strings', []);
    foreach ($requested_string_translations as $requested_string_translation) {
      $this->queue->createItem($requested_string_translation);
    }
  }

}
