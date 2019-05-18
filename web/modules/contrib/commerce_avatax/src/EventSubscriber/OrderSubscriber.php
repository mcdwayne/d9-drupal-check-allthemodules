<?php

namespace Drupal\commerce_avatax\EventSubscriber;

use Drupal\commerce_avatax\Avatax;
use Drupal\commerce_avatax\AvataxLib;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderSubscriber implements EventSubscriberInterface {

  /**
   * The Avatax library.
   *
   * @var \Drupal\commerce_avatax\AvataxLib
   */
  protected $avataxLib;

  /**
   * The Avatax configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new CommitTransactionSubscriber object.
   *
   * @param \Drupal\commerce_avatax\AvataxLib $avatax_lib
   *   The Avatax library.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(AvataxLib $avatax_lib, ConfigFactoryInterface $config_factory) {
    $this->avataxLib = $avatax_lib;
    $this->config = $config_factory->get('commerce_avatax.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.post_transition' => ['commitTransaction'],
      'commerce_order.cancel.pre_transition' => ['onOrderCancel'],
      OrderEvents::ORDER_DELETE => ['onOrderDelete'],
    ];
    return $events;
  }

  /**
   * Commits a transaction or the order in AvaTax.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function commitTransaction(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    if ($this->config->get('disable_commit') || !Avatax::hasAvataxAdjustments($order)) {
      return;
    }

    $this->avataxLib->transactionsCreate($order, 'SalesInvoice');
  }

  /**
   * Voids the Avatax transaction on order cancellation.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function onOrderCancel(WorkflowTransitionEvent $event) {
    $this->voidTransaction($event->getEntity());
  }

  /**
   * Voids the Avatax transaction on order deletion.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The order event.
   */
  public function onOrderDelete(OrderEvent $event) {
    $this->voidTransaction($event->getOrder());
  }

  /**
   * Voids a transaction in AvaTax for the given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  protected function voidTransaction(OrderInterface $order) {
    if (!Avatax::hasAvataxAdjustments($order)) {
      return;
    }

    $this->avataxLib->transactionsVoid($order);
  }

}
