<?php

declare(strict_types = 1);

namespace Drupal\sendwithus_commerce\Resolver\Variable;

use Drupal\address\AddressInterface;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderTotalSummaryInterface;
use Drupal\commerce_price\Price;
use Drupal\sendwithus\Context;
use Drupal\sendwithus\Resolver\Variable\VariableCollectorInterface;
use Drupal\sendwithus\Template;

/**
 * Provides a variable collector for commerce order module.
 */
class OrderVariableCollector implements VariableCollectorInterface {

  protected $orderTotalSummary;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\commerce_order\OrderTotalSummaryInterface $orderTotalSummary
   *   The order total summary.
   */
  public function __construct(OrderTotalSummaryInterface $orderTotalSummary) {
    $this->orderTotalSummary = $orderTotalSummary;
  }

  /**
   * {@inheritdoc}
   */
  public function collect(Template $template, Context $context) : void {
    $order = $context->getData()->get('params')['order'] ?? NULL;

    if (!$order instanceof OrderInterface) {
      return;
    }

    $variables = [
      'id' => $order->id(),
      'mail' => $order->getEmail(),
      'type' => $order->bundle(),
      'customer' => [
        'id' => $order->getCustomer()->id(),
        'name' => $order->getCustomer()->getDisplayName(),
        'ip' => $order->getIpAddress(),
      ],
      'order_number' => $order->getOrderNumber(),
      'store' => [
        'id' => $order->getStore()->id(),
        'label' => $order->getStore()->label(),
        'type' => $order->getStore()->bundle(),
      ],
      'adjustments' => [],
      'items' => [],
      'is_locked' => $order->isLocked(),
      'created' => $order->getCreatedTime(),
      'placed' => $order->getPlacedTime(),
      'completed' => $order->getCompletedTime(),
      'state' => [
        'label' => $order->getState()->getLabel(),
        'value' => $order->getState()->value,
      ],
      'payment_method' => [],
      'payment_gateway' => [],
    ];

    $totals = $this->orderTotalSummary->buildTotals($order);

    $variables['totals'] = [
      'subtotal' => $this->collectPrice($totals['subtotal']),
      'total' => $this->collectPrice($totals['total']),
      'adjustments' => array_map(function (array $item) {
        // Convert price objects to array.
        $item['total'] = $this->collectPrice($item['total']);
        $item['amount'] = $this->collectPrice($item['amount']);

        if ($item['percentage']) {
          $item['percentage'] = ((float) $item['percentage'] * 100);
        }

        return $item;
      }, $totals['adjustments']),
    ];

    foreach ($order->getItems() as $orderItem) {
      $item = [
        'id' => $orderItem->id(),
        'label' => $orderItem->getTitle(),
        'quantity' => (int) $orderItem->getQuantity(),
        'unit_price' => $this->collectPrice($orderItem->getUnitPrice()),
        'is_unit_price_overridden' => $orderItem->isUnitPriceOverridden(),
        'adjusted_unit_price' => $this->collectPrice($orderItem->getAdjustedUnitPrice()),
        'adjustments' => [],
        'total_price' => $this->collectPrice($orderItem->getTotalPrice()),
        'adjusted_total_price' => $this->collectPrice($orderItem->getAdjustedTotalPrice()),
        'created' => $orderItem->getCreatedTime(),
      ];

      foreach ($orderItem->getAdjustments() as $adjustment) {
        $item['adjustments'][] = $this->collectAdjustment($adjustment);
      }

      if ($purchasedEntity = $orderItem->getPurchasedEntity()) {
        $item['purchased_entity'] = [
          'id' => $purchasedEntity->id(),
          'label' => $purchasedEntity->label(),
          'type' => $purchasedEntity->getOrderItemTypeId(),
          'price' => $this->collectPrice($purchasedEntity->getPrice()),
        ];
      }
      $variables['items'][] = $item;
    }

    if ($order->hasField('payment_method')) {
      /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
      if ($payment_method = $order->get('payment_method')->entity) {
        $variables['payment_method'] = [
          'label' => $payment_method->label(),
          'id' => $payment_method->id(),
          'created' => $payment_method->getCreatedTime(),
          'type' => [
            'id' => $payment_method->getType()->getPluginId(),
            'label' => $payment_method->getType()->getLabel(),
          ],
        ];
      }
      /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gw */
      if ($payment_gw = $order->get('payment_gateway')->entity) {
        $variables['payment_gateway'] = [
          'label' => $payment_gw->label(),
          'id' => $payment_gw->id(),
        ];
      }
    }
    foreach ($order->getAdjustments() as $adjustment) {
      $variables['adjustments'][] = $this->collectAdjustment($adjustment);
    }

    if ($billing_profile = $order->getBillingProfile()) {
      /** @var \Drupal\address\AddressInterface $address */
      $address = $billing_profile->get('address')->first();

      $variables['billing']['address'] = $this->collectAddressInfo($address);
    }
    $template->setTemplateVariable('order', $variables);
  }

  /**
   * Collects price data.
   *
   * @param \Drupal\commerce_price\Price|null $price
   *   The price.
   *
   * @return array
   *   The price data.
   */
  protected function collectPrice(Price $price = NULL) : array {
    if (!$price) {
      return [];
    }
    return [
      'number' => (float) $price->getNumber(),
      'currency_code' => $price->getCurrencyCode(),
    ];
  }

  /**
   * Collects adjustment data.
   *
   * @param \Drupal\commerce_order\Adjustment $adjustment
   *   The adjustment.
   *
   * @return array
   *   The adjustment.
   */
  protected function collectAdjustment(Adjustment $adjustment) : array {
    return [
      'type' => $adjustment->getType(),
      'amount' => $this->collectPrice($adjustment->getAmount()),
      'percentage' => $adjustment->getPercentage() ? ((float) $adjustment->getPercentage() * 100) : NULL,
      'label' => $adjustment->getLabel(),
    ];
  }

  /**
   * Gets the address data for given address.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address.
   *
   * @return array
   *   The address data.
   */
  protected function collectAddressInfo(AddressInterface $address) : array {
    return [
      'country_code' => $address->getCountryCode(),
      'administrative_area' => $address->getAdministrativeArea(),
      'locality' => $address->getLocality(),
      'dependent_locality' => $address->getDependentLocality(),
      'postal_code' => $address->getPostalCode(),
      'sorting_code' => $address->getSortingCode(),
      'address_line1' => $address->getAddressLine1(),
      'address_line2' => $address->getAddressLine2(),
      'organization' => $address->getOrganization(),
      'given_name' => $address->getGivenName(),
      'additional_name' => $address->getAdditionalName(),
      'family_name' => $address->getFamilyName(),
      'locale' => $address->getLocale(),
    ];
  }

}
