<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Service;

use Drupal\commerce_klarna_payments\LocaleResolverInterface;
use Drupal\commerce_klarna_payments\OptionsHelper;
use Drupal\commerce_klarna_payments\Plugin\Commerce\PaymentGateway\Klarna;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a base class to create request builders.
 */
abstract class RequestBuilderBase {

  use OptionsHelper;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The payment gateway plugin.
   *
   * @var \Drupal\commerce_klarna_payments\Plugin\Commerce\PaymentGateway\Klarna
   */
  protected $plugin;

  /**
   * The locale resolver.
   *
   * @var \Drupal\commerce_klarna_payments\LocaleResolverInterface
   */
  protected $localeResolver;

  /**
   * Constructs a new instance.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\commerce_klarna_payments\LocaleResolverInterface $resolver
   *   The resolver.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher, LocaleResolverInterface $resolver) {
    $this->eventDispatcher = $eventDispatcher;
    $this->localeResolver = $resolver;
  }

  /**
   * Gets the payment plugin for order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\commerce_klarna_payments\Plugin\Commerce\PaymentGateway\Klarna
   *   The payment plugin.
   */
  protected function getPaymentPlugin(OrderInterface $order) : Klarna {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $entity */
    $entity = $order->payment_gateway->entity;

    return $entity->getPlugin();
  }

  /**
   * Populates the order and plugin.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return $this
   *   The self.
   */
  public function setOrder(OrderInterface $order) : self {
    $this->order = $order;
    $this->plugin = $this->getPaymentPlugin($order);

    return $this;
  }

  /**
   * Constructs a new instance with order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return $this
   *   The self.
   */
  public function withOrder(OrderInterface $order) : self {
    $instance = clone $this;
    $instance->setOrder($order);

    return $instance;
  }

}
