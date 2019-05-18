<?php

/**
 * @file
 * Contains \Drupal\monster_menus\EventSubscriber\ExitSubscriber.
 */

namespace Drupal\monster_menus\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExitSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::TERMINATE => ['onEvent', 0]];
  }

  public function onEvent() {
    // Process any queued changes to the sort index
    mm_content_update_sort_queue();

    // Get the list of pages whose permissions or location in the tree have
    // changed and remove entries from mm_access_cache for all nodes appearing on
    // these pages and their children.
    _mm_content_clear_access_cache();
  }

}
