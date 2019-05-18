<?php

namespace Drupal\commerce_klaviyo\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_klaviyo\CustomerProperties;
use Drupal\commerce_klaviyo\OrderProperties;
use Drupal\commerce_klaviyo\Util\KlaviyoRequestInterface;
use Drupal\commerce_klaviyo\Util\KlaviyoRequestTrait;
use Drupal\commerce_order\OrderTotalSummaryInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Order events subscriber for notifying Klaviyo about placing/fulfillment.
 */
class OrderEventSubscriber implements EventSubscriberInterface {

  use KlaviyoRequestTrait;

  /**
   * The order total summary service.
   *
   * @var \Drupal\commerce_order\OrderTotalSummaryInterface
   */
  protected $orderTotalSummary;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates an instance of the class.
   *
   * @param \Drupal\commerce_order\OrderTotalSummaryInterface $order_total_summary
   *   The order total summary service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(OrderTotalSummaryInterface $order_total_summary, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->orderTotalSummary = $order_total_summary;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'commerce_order.place.post_transition' => 'onPlaceTransition',
      'commerce_order.fulfill.post_transition' => 'onFulfillTransition',
    ];
    return $events;
  }

  /**
   * Notifies Klaviyo about the "Placed order" transition.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   See \Drupal\Core\Entity\EntityTypeManagerInterface::getStorage().
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   See \Drupal\Core\Entity\EntityTypeManagerInterface::getStorage().
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   See Drupal\commerce_klaviyo\CustomerProperties::createFromUser().
   */
  public function onPlaceTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $customer_id = $order->getCustomerId();

    if ($customer_id) {
      $user = $this->entityTypeManager
        ->getStorage('user')
        ->load($customer_id);
    }

    if (!empty($user)) {
      $customer_properties = CustomerProperties::createFromUser($user);
    }
    elseif ($order->getEmail()) {
      $customer_properties = CustomerProperties::createFromOrder($order);
    }

    if (isset($customer_properties)) {
      $properties = new OrderProperties($this->configFactory, $order);
      /** @var \Drupal\commerce_order\OrderTotalSummaryInterface $order_total_summary */
      $totals = $this->orderTotalSummary->buildTotals($order);
      $adjustments_total = 0;

      foreach ($totals['adjustments'] as $adjustment) {
        if ('promotion' == $adjustment['type']) {
          /** @var \Drupal\commerce_price\Price $amount */
          $amount = $adjustment['amount'];
          $adjustments_total += $amount->getNumber();
        }
      }

      $properties->setProperty('Discount Value', abs($adjustments_total));

      $klaviyo = $this->getKlaviyoRequest();
      $klaviyo->track(KlaviyoRequestInterface::PLACED_ORDER_EVENT, $customer_properties, $properties, NULL, TRUE);
      $klaviyo->track(KlaviyoRequestInterface::ORDERED_PRODUCT_EVENT, $customer_properties, $properties, NULL, TRUE);
    }
  }

  /**
   * Notifies Klaviyo about the "Order fulfillment" transition.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   See \Drupal\Core\Entity\EntityTypeManagerInterface::getStorage().
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   See \Drupal\Core\Entity\EntityTypeManagerInterface::getStorage().
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   See Drupal\commerce_klaviyo\CustomerProperties::createFromUser().
   */
  public function onFulfillTransition(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $customer_id = $order->getCustomerId();

    if ($customer_id) {
      $user = $this->entityTypeManager
        ->getStorage('user')
        ->load($customer_id);
    }

    if (!empty($user)) {
      $customer_properties = CustomerProperties::createFromUser($user);
    }
    elseif ($order->getEmail()) {
      $customer_properties = CustomerProperties::createFromOrder($order);
    }

    if (isset($customer_properties)) {
      $properties = new OrderProperties($this->configFactory, $order);
      $this->getKlaviyoRequest()
        ->track(KlaviyoRequestInterface::FULFILLED_ORDER_EVENT, $customer_properties, $properties, NULL, TRUE);
    }
  }

}
