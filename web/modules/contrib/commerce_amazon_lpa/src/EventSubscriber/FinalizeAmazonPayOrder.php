<?php

namespace Drupal\commerce_amazon_lpa\EventSubscriber;

use Drupal\commerce_amazon_lpa\AmazonPay;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for amazon pay.
 */
class FinalizeAmazonPayOrder implements EventSubscriberInterface {

  /**
   * The Amazon Pay client.
   *
   * @var \Drupal\commerce_amazon_lpa\AmazonPay
   */
  protected $amazonPay;

  /**
   * The Amazon Pay order to finalize.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructor.
   */
  public function __construct(AmazonPay $amazon_pay) {
    $this->amazonPay = $amazon_pay;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.post_transition' => 'flagOrder',
      KernelEvents::TERMINATE => 'finalizeOrder',
    ];
    return $events;
  }

  /**
   * Flag the order to be finalized.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function flagOrder(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $this->order = $order;
  }

  /**
   * Finalizes the order.
   *
   * This includes synchronization of billing and shipping profiles, along with
   * closing the order reference.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The event.
   */
  public function finalizeOrder(PostResponseEvent $event) {
    if (!empty($this->order)) {
      $this->amazonPay->closeOrderReference($this->order);
    }
  }

}
