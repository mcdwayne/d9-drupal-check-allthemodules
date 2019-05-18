<?php

namespace Drupal\commerce_amazon_lpa\EventSubscriber;

use Drupal\commerce\ConditionManagerInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscrber for shipment.
 */
class CaptureOnShipmentSubscriber implements EventSubscriberInterface {

  /**
   * The Amazon Pay settings.
   *
   * @var array|\Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig|mixed|null
   */
  protected $amazonPaySettings;

  /**
   * The condition manager.
   *
   * @var \Drupal\commerce\ConditionManagerInterface
   */
  protected $conditionManager;

  /**
   * The payment storage.
   *
   * @var \Drupal\commerce_payment\PaymentStorageInterface
   */
  protected $paymentStorage;

  /**
   * The Amazon Pay gateways.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface
   */
  protected $amazonPayGateway;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConditionManagerInterface $condition_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->amazonPaySettings = $config_factory->get('commerce_amazon_lpa.settings');
    $this->conditionManager = $condition_manager;
    $this->paymentStorage = $entity_type_manager->getStorage('commerce_payment');
    $this->amazonPayGateway = $entity_type_manager->getStorage('commerce_payment_gateway')->load('amazon_pay');

  }

  /**
   * Captures payment if the order is shipped.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The order event.
   */
  public function captureShipment(OrderEvent $event) {
    /** @var \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface $condition */
    $condition = $this->conditionManager->createInstance('amazon_order');
    $order = $event->getOrder();
    if ($condition->evaluate($order) && $order->getState()->value == 'completed') {
      if ($this->amazonPaySettings->get('capture_mode') == 'shipment_capture') {
        $payments = $this->paymentStorage->loadMultipleByOrder($order);
        $payments = array_filter($payments, function ($payment) {
          return $payment->getPaymentGatewayId() == $this->amazonPayGateway->id();
        });
        $payment = reset($payments);
        $this->amazonPayGateway->getPlugin()->capturePayment($payment);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OrderEvents::ORDER_PRESAVE => 'captureShipment',
    ];
  }

}
