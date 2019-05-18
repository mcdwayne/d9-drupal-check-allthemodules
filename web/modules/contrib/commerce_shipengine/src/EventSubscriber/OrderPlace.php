<?php

namespace Drupal\commerce_shipengine\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\commerce_shipengine\ShipEngineLabelRequest;

/**
 * Class OrderPlace.
 *
 * @package Drupal\commerce_shipengine\EventSubscriber
 */
class OrderPlace implements EventSubscriberInterface {

  /**
   * Constructs a new iConnectQueueOrder object.
   */
  public function __construct(ShipEngineLabelRequest $shipengine_label_request) {
    $this->shipengine_label_request = $shipengine_label_request;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = ['commerce_order.place.pre_transition' => 'preOrderPlace'];
    return $events;
  }

  /**
   * Create shipping label.
   */
  public function preOrderPlace(WorkflowTransitionEvent $event) {
    $order = $event->getEntity();
    $shipments = $order->get('shipments')->referencedEntities();

    foreach ($shipments as $shipment) {
      $this->shipengine_label_request->setShipment($shipment);
      $label = $this->shipengine_label_request->createLabel();
      $shipment->setData('label', $label);
      $shipment->save();
    }
  }

}
