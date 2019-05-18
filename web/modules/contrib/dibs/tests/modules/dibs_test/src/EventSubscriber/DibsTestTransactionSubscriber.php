<?php

namespace Drupal\dibs_test\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\dibs\Event\AcceptTransactionEvent;
use Drupal\dibs\Event\ApproveTransactionEvent;
use Drupal\dibs\Event\CancelTransactionEvent;
use Drupal\dibs\Event\DibsEvents;
use Drupal\novasol_api\API;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DibsTestTransactionSubscriber implements EventSubscriberInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  function __construct(StateInterface $state) {
    $this->state = $state;
  }

  public function acceptTransaction(AcceptTransactionEvent $event) {
    $this->storeEvent(DibsEvents::ACCEPT_TRANSACTION);
  }

  public function approveTransaction(ApproveTransactionEvent $event) {
    $this->storeEvent(DibsEvents::APPROVE_TRANSACTION);
  }

  public function cancelTransaction(CancelTransactionEvent $event) {
    $this->storeEvent(DibsEvents::CANCEL_TRANSACTION);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[DibsEvents::ACCEPT_TRANSACTION] = 'acceptTransaction';
    $events[DibsEvents::CANCEL_TRANSACTION] = 'cancelTransaction';
    $events[DibsEvents::APPROVE_TRANSACTION] = 'approveTransaction';

    return $events;
  }

  /**
   * Stores the specified event.
   *
   * @param string $event_name
   *   The event name.
   */
  protected function storeEvent($event_name) {
    $this->state->set($event_name, TRUE);
  }
}
