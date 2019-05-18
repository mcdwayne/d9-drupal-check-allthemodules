<?php

namespace Drupal\page_hits\EventSubscriber;

use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Logs page hits.
 */
class PageHitsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = ['initialize', 100];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public static function initialize(Event $event) {
    global $base_url;

    $admin = in_array('administrator', \Drupal::currentUser()->getRoles());
    $log_for_admin = \Drupal::config('page_hits.settings')->get('increment_page_count_for_admin');

    $logEntry = TRUE;
    if ($admin && !$log_for_admin) {
      $logEntry = FALSE;
    }

    $node = \Drupal::request()->attributes->get('node');
    $is_admin_interface = \Drupal::service('router.admin_context')->isAdminRoute();
    if (!$is_admin_interface && ($event->getResponse() instanceof HtmlResponse) && $logEntry) {
      $fields = [];
      $fields['ip'] = \Drupal::request()->getClientIp();
      $session_manager = \Drupal::service('session_manager');
      $fields['session_id'] = $session_manager->getId();
      $fields['url'] = $base_url . \Drupal::request()->getRequestUri();
      $fields['uid'] = \Drupal::currentUser()->id();
      $fields['nid'] = (!empty($node) ? $node->id() : '0');
      $fields['created'] = \Drupal::time()->getRequestTime();

      // Insert the record to table.
      \Drupal::database()->insert('page_hits')
        ->fields($fields)
        ->execute();
    }
  }

}
