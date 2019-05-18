<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Service\Payment;

use Drupal\commerce_klarna_payments\Klarna\Data\AddressInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Order\CreateCaptureInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\OrderItemInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\OrderItemTypeInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\AuthorizationRequestInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\RequestInterface as RequestInterfaceBase;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\RequestInterface;
use Drupal\commerce_klarna_payments\Klarna\Request\Address;
use Drupal\commerce_klarna_payments\Klarna\Request\MerchantUrlset;
use Drupal\commerce_klarna_payments\Klarna\Request\Order\CaptureRequest;
use Drupal\commerce_klarna_payments\Klarna\Request\OrderItem;
use Drupal\commerce_klarna_payments\Klarna\Request\Payment\AuthorizationRequest;
use Drupal\commerce_klarna_payments\Klarna\Request\Payment\Request;
use Drupal\commerce_klarna_payments\Klarna\Service\RequestBuilderBase;
use Drupal\commerce_order\Entity\OrderItemInterface as CommerceOrderItemInterface;
use Drupal\commerce_price\Calculator;

/**
 * Provides a request builder.
 */
class RequestBuilder extends RequestBuilderBase {

  /**
   * Populates request object for given type.
   *
   * @param string $type
   *   The request type.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\RequestInterface
   *   The request.
   */
  public function generateRequest(string $type) : RequestInterfaceBase {
    switch ($type) {
      case 'create':
      case 'update':
        return $this->createUpdateRequest(new Request());

      case 'place':
        return $this->createPlaceRequest(new AuthorizationRequest());

      case 'capture':
        return $this->createCaptureRequest(new CaptureRequest());
    }
    throw new \LogicException('Invalid type.');
  }

  /**
   * Creates capture request object.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Order\CreateCaptureInterface $request
   *   The request.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\Order\CreateCaptureInterface
   *   The capture request.
   */
  protected function createCaptureRequest(CreateCaptureInterface $request) : CreateCaptureInterface {
    $balance = $this->order->getBalance();

    $request->setCapturedAmount((int) $balance->multiply('100')->getNumber());

    foreach ($this->order->getItems() as $item) {
      $orderItem = $this->createOrderLine($item);

      $request->addOrderItem($orderItem);
    }

    return $request;
  }

  /**
   * Creates update/create request object.
   *
   * @todo Figure out how to deal with minor units.
   * @todo Support shipping fees.
   * @todo Figure out what to do when order is in PENDING state.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\RequestInterface $request
   *   The request.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\Payment\RequestInterface
   *   The request.
   */
  protected function createUpdateRequest(RequestInterface $request) : RequestInterface {
    $totalPrice = $this->order->getTotalPrice();

    $request
      ->setPurchaseCountry($this->getStoreLanguage())
      ->setPurchaseCurrency($totalPrice->getCurrencyCode())
      ->setLocale($this->localeResolver->resolve($this->order))
      ->setOrderAmount((int) $totalPrice->multiply('100')->getNumber())
      ->setMerchantUrls(
        new MerchantUrlset([
          'confirmation' => $this->plugin->getReturnUri($this->order, 'commerce_payment.checkout.return'),
          // @todo Implement this.
          'notification' => $this->plugin->getReturnUri($this->order, 'commerce_payment.notify', [
            'step' => 'complete',
          ]),
        ])
      )
      ->setOptions($this->getOptions());

    if ($billingAddress = $this->getBillingAddress()) {
      $request->setBillingAddress($billingAddress);
    }

    $totalTax = 0;

    foreach ($this->order->getItems() as $item) {
      $orderItem = $this->createOrderLine($item);
      $request->addOrderItem($orderItem);

      // Collect taxes only if enabled.
      if ($this->hasTaxesIncluded()) {
        // Calculate total tax amount.
        $totalTax += $orderItem->getTotalTaxAmount();
      }
    }
    $request->setOrderTaxAmount($totalTax);

    // Inspect order adjustments to include shipping fees.
    foreach ($this->order->getAdjustments() as $orderAdjustment) {
      $amount = (int) $orderAdjustment->getAmount()->multiply('100')->getNumber();

      switch ($orderAdjustment->getType()) {
        case 'shipping':
          // @todo keep watching progress of https://www.drupal.org/node/2874158.
          $shippingOrderItem = (new OrderItem())
            ->setName((string) $orderAdjustment->getLabel())
            ->setQuantity(1)
            ->setUnitPrice($amount)
            ->setTotalAmount($amount)
            ->setType(OrderItemTypeInterface::TYPE_SHIPPING_FEE);

          $request->addOrderItem($shippingOrderItem);
          break;
      }
    }

    return $request;
  }

  /**
   * Checks whether taxes are included in prices or not.
   *
   * @return bool
   *   TRUE if taxes are included, FALSE if not.
   */
  protected function hasTaxesIncluded() : bool {
    static $taxesIncluded;

    if ($taxesIncluded === NULL) {
      if (!$this->order->getStore()->hasField('prices_include_tax')) {
        $taxesIncluded = FALSE;

        return FALSE;
      }
      $taxesIncluded = (bool) $this->order->getStore()->get('prices_include_tax');
    }
    return $taxesIncluded;
  }

  /**
   * Creates new order line.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $item
   *   The order item to create order line from.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\OrderItemInterface
   *   The order line item.
   */
  protected function createOrderLine(CommerceOrderItemInterface $item) : OrderItemInterface {
    $unitPrice = (int) $item->getAdjustedUnitPrice()->multiply('100')->getNumber();

    $orderItem = (new OrderItem())
      ->setName((string) $item->getTitle())
      ->setQuantity((int) $item->getQuantity())
      ->setUnitPrice($unitPrice)
      ->setTotalAmount((int) ($unitPrice * $item->getQuantity()));

    foreach ($item->getAdjustments() as $adjustment) {
      // Only tax adjustments are supported by default.
      if ($adjustment->getType() !== 'tax') {
        continue;
      }
      $tax = (int) $adjustment->getAmount()->multiply('100')->getNumber();
      if ($item->usesLegacyAdjustments()) {
        $tax = (int) ($tax * $item->getQuantity());
      }

      if (!$percentage = $adjustment->getPercentage()) {
        $percentage = '0';
      }
      // Multiply tax rate to have two implicit decimals, 2500 = 25%.
      $orderItem->setTaxRate((int) Calculator::multiply($percentage, '10000'))
        // Calculate total tax for order item.
        ->setTotalTaxAmount($tax);
    }

    return $orderItem;
  }

  /**
   * Gets the billing address.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\AddressInterface|null
   *   The billing address or null.
   */
  protected function getBillingAddress() : ? AddressInterface {
    if (!$billingData = $this->order->getBillingProfile()) {
      return NULL;
    }
    /** @var \Drupal\address\AddressInterface $address */
    $address = $billingData->get('address')->first();

    $profile = (new Address())
      ->setEmail($this->order->getEmail());

    if ($code = $address->getCountryCode()) {
      $profile->setCountry($code);
    }

    if ($city = $address->getLocality()) {
      $profile->setCity($city);
    }

    if ($addr = $address->getAddressLine1()) {
      $profile->setStreetAddress($addr);
    }

    if ($addr2 = $address->getAddressLine2()) {
      $profile->setStreetAddress2($addr2);
    }

    if ($firstName = $address->getGivenName()) {
      $profile->setGivenName($firstName);
    }

    if ($lastName = $address->getFamilyName()) {
      $profile->setFamilyName($lastName);
    }

    if ($postalCode = $address->getPostalCode()) {
      $profile->setPostalCode($postalCode);
    }

    return $profile;
  }

  /**
   * Gets the store language.
   *
   * @return string
   *   The language.
   */
  protected function getStoreLanguage() : string {
    return $this->order->getStore()->getAddress()->getCountryCode();
  }

  /**
   * Generates place order request object.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\AuthorizationRequestInterface $request
   *   The authorization request.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\Payment\AuthorizationRequestInterface
   *   The request.
   */
  protected function createPlaceRequest(AuthorizationRequestInterface $request) : AuthorizationRequestInterface {
    $request = $this->createUpdateRequest($request);

    $this->validate($request, [
      'purchase_country',
      'purchase_currency',
      'locale',
      'order_amount',
      'order_lines',
      'merchant_urls',
      'billing_address',
    ]);
    return $request;
  }

  /**
   * Validates the requests.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\RequestInterface $request
   *   The request.
   * @param array $required
   *   Required fields.
   */
  protected function validate(RequestInterface $request, array $required) : void {
    $values = $request->toArray();

    $missing = array_filter($required, function ($key) use ($values) {
      return !isset($values[$key]);
    });

    if (count($missing) > 0) {
      throw new \InvalidArgumentException(sprintf('Missing required values: %s', implode(',', $missing)));
    }
  }

}
