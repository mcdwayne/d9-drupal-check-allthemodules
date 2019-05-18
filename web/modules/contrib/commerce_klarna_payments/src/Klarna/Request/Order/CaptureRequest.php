<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Request\Order;

use Drupal\commerce_klarna_payments\Klarna\Data\Order\CreateCaptureInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\OrderItemInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\ShippingInformationInterface;
use Drupal\commerce_klarna_payments\Klarna\RequestBase;
use Webmozart\Assert\Assert;

/**
 * Value object to send capture requests.
 */
class CaptureRequest extends RequestBase implements CreateCaptureInterface {

  protected $data = [];

  /**
   * {@inheritdoc}
   */
  public function setCapturedAmount(int $amount) : CreateCaptureInterface {
    $this->data['captured_amount'] = $amount;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription(string $description) : CreateCaptureInterface {
    $this->data['description'] = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderItems(array $orderItems) : CreateCaptureInterface {
    Assert::allIsInstanceOf($orderItems, OrderItemInterface::class);

    $this->data['order_lines'] = $orderItems;
    return $this;
  }

  /**
   * Gets the order items.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\OrderItemInterface[]
   *   The order lines.
   */
  public function getOrderItems() : array {
    return $this->data['order_lines'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function addOrderItem(OrderItemInterface $orderItem) : CreateCaptureInterface {
    $this->data['order_lines'][] = $orderItem;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setShippingInformation(ShippingInformationInterface $info) : CreateCaptureInterface {
    $this->data['shipping_info'] = $info;
    return $this;
  }

  /**
   * Gets the shipping info.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\ShippingInformationInterface|null
   *   The shipping info or NULL.
   */
  public function getShippinfInformation() : ? ShippingInformationInterface {
    return $this->data['shipping_info'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setShippingDelay(int $delay) : CreateCaptureInterface {
    $this->data['shipping_delay'] = $delay;
    return $this;
  }

}
