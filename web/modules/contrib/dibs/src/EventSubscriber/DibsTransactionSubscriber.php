<?php

namespace Drupal\dibs\EventSubscriber;

use Drupal\dibs\Event\AcceptTransactionEvent;
use Drupal\dibs\Event\ApproveTransactionEvent;
use Drupal\dibs\Event\DibsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DibsTransactionSubscriber implements EventSubscriberInterface {

  public function __construct() {
  }

  public function acceptTransaction(AcceptTransactionEvent $event) {
    $event->getTransaction()->set('status', 'ACCEPTED')->save();
  }

  public function approveTransaction(ApproveTransactionEvent $event) {
    $event->getTransaction()->set('status', 'APPROVED')->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[DibsEvents::ACCEPT_TRANSACTION] = 'acceptTransaction';
    $events[DibsEvents::APPROVE_TRANSACTION] = 'approveTransaction';

    return $events;
  }
}
