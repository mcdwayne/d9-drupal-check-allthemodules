<?php

namespace Drupal\commerce_admin_payment;

use Drupal\commerce_admin_payment\Plugin\Commerce\PaymentGateway\AdminManual;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Event\FilterPaymentGatewaysEvent;
use Drupal\commerce_payment\Event\PaymentEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AdminManualPaymentManager implements AdminManualPaymentManagerInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * AdminManualPaymentManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entityTypeManager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminManualPaymentGateways(OrderInterface $order) {
    return array_filter($this->loadGatewaysForOrder($order), function($gateway) {
      /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $gateway */
      $payment_gateway_plugin = $gateway->getPlugin();
      return ($payment_gateway_plugin instanceof AdminManual);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function loadPaymentGateway($payment_gateway_id) {
    return $this->entityTypeManager->getStorage('commerce_payment_gateway')->load($payment_gateway_id);
  }
  
  /**
   * {@inheritdoc}
   */
  protected function loadGatewaysForOrder(OrderInterface $order) {
    /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $payment_gateway_storage */
    $payment_gateway_storage = $this->entityTypeManager->getStorage('commerce_payment_gateway');

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $payment_gateways */
    $payment_gateways = $payment_gateway_storage->loadMultiple();
    // Allow the list of payment gateways to be filtered via code.
    $order->is_admin_payment_pane = TRUE;
    $event = new FilterPaymentGatewaysEvent($payment_gateways, $order);
    $this->eventDispatcher->dispatch(PaymentEvents::FILTER_PAYMENT_GATEWAYS, $event);
    $payment_gateways = $event->getPaymentGateways();
    // Evaluate conditions for the remaining ones.
    foreach ($payment_gateways as $payment_gateway_id => $payment_gateway) {
      if (!$payment_gateway->applies($order)) {
        unset($payment_gateways[$payment_gateway_id]);
      }
    }
    $entity_type = $this->entityTypeManager->getStorage('commerce_payment_gateway')->getEntityType();
    uasort($payment_gateways, [$entity_type->getClass(), 'sort']);
    $order->is_admin_payment_pane = FALSE;

    return $payment_gateways;
  }

}
