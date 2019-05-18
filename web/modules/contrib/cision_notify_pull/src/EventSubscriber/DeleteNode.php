<?php

namespace Drupal\cision_notify_pull\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\cision_notify_pull\Event\CisionEvent;

/**
 * Class DeleteNode.
 *
 * @package Drupal\cision_notify_pull
 */
class DeleteNode implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['cision_notify_pull.delete.item.after.addtran'][] = ['deleteItemAfterAddTrans'];
    return $events;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\cision_notify_pull\Event\CisionEvent $event
   *   Event {@inheritdoc}.
   */
  public function deleteItemAfterAddTrans(CisionEvent $event) {
    $node = $event->getNode();
    $node->delete();
  }

}
