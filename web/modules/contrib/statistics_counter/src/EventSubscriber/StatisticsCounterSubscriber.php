<?php

namespace Drupal\statistics_counter\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribe to KernelEvents::TERMINATE events to recalculate nodes statistics.
 */
class StatisticsCounterSubscriber implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = ['updateStatistics'];
    return $events;
  }

  /**
   * Update statistics.
   *
   * @param Symfony\Component\EventDispatcher\Event $event
   *   Event.
   */
  static function updateStatistics(Event $event) {
    $node = \Drupal::request()->attributes->get('node');
    $views = \Drupal::config('statistics.settings')->get('count_content_views');

    if ($node && ($event->getResponse() instanceof HtmlResponse) && $views) {
      // Support statistics filter.
      if (\Drupal::moduleHandler()->moduleExists('statistics_filter') && statistics_filter_do_filter()) {
        return;
      }

      // We are counting content views.
      // A node has been viewed, so update the node's counters.
      db_merge('node_counter')
        ->key(array('nid' => $node->id()))
        ->fields(array(
          'weekcount' => 1,
          'monthcount' => 1,
          'yearcount' => 1,
        ))
        ->expression('weekcount', 'weekcount + 1')
        ->expression('monthcount', 'monthcount + 1')
        ->expression('yearcount', 'yearcount + 1')
        ->execute();
    }
  }
}
