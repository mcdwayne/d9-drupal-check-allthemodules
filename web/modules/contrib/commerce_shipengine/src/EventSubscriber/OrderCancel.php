<?php

namespace Drupal\commerce_shipengine\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\commerce_shipengine\ShipEngineVoidRequest;

/**
 * Class OrderCancel.
 *
 * @package Drupal\commerce_shipengine\EventSubscriber
 */
class OrderCancel implements EventSubscriberInterface {

  /**
   * Constructs a new iConnectQueueOrder object.
   */
  public function __construct(ShipEngineVoidRequest $shipengine_void_request) {
    $this->shipengine_void_request = $shipengine_void_request;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = ['commerce_order.cancel.post_transition' => 'postOrderCancel'];
    return $events;
  }

  /**
   * Create shipping label.
   */
  public function postOrderCancel(WorkflowTransitionEvent $event) {
    $order = $event->getEntity();
    $shipments = $order->get('shipments')->referencedEntities();

    foreach ($shipments as $shipment) {
      $this->shipengine_void_request->setShipment($shipment);
      $label = $shipment->getData('label');
      if ($label) {
        $this->shipengine_void_request->voidLabel($label['label_id']);
        $shipment->setData('label', []);
        $shipment->save();
      }
    }
  }

}
