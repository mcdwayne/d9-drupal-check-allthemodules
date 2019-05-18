<?php

namespace Drupal\Tests\commerce_migrate\Kernel;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\address\AddressInterface;
use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_price\Entity\CurrencyInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_shipping\Entity\ShippingMethod;
use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_tax\Entity\TaxType;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;

/**
 * Helper function to test migrations.
 */
trait CommerceMigrateTestTrait {

  /**
   * Asserts an address field.
   *
   * @param array $address
   *   The address id.
   * @param string $country_code
   *   The country code.
   * @param string $administrative_area
   *   The administrative area.
   * @param string $locality
   *   The locality.
   * @param string $dependent_locality
   *   The dependent locality.
   * @param string $postal_code
   *   The postal code.
   * @param string $sorting_code
   *   The sorting code.
   * @param string $address_line_1
   *   Address line 1.
   * @param string $address_line_2
   *   Address line 2.
   * @param string $given_name
   *   The given name.
   * @param string $additional_name
   *   Any additional names.
   * @param string $family_name
   *   The family name.
   * @param string $organization
   *   The organization string.
   */
  public function assertAddressField(array $address, $country_code, $administrative_area, $locality, $dependent_locality, $postal_code, $sorting_code, $address_line_1, $address_line_2, $given_name, $additional_name, $family_name, $organization) {
    $this->assertSame($country_code, $address['country_code']);
    $this->assertSame($administrative_area, $address['administrative_area']);
    $this->assertSame($locality, $address['locality']);
    $this->assertSame($dependent_locality, $address['dependent_locality']);
    $this->assertSame($postal_code, $address['postal_code']);
    $this->assertSame($sorting_code, $address['sorting_code']);
    $this->assertSame($address_line_1, $address['address_line1']);
    $this->assertSame($address_line_2, $address['address_line2']);
    $this->assertSame($given_name, $address['given_name']);
    $this->assertSame($additional_name, $address['additional_name']);
    $this->assertSame($family_name, $address['family_name']);
    $this->assertSame($organization, $address['organization']);
  }

  /**
   * Asserts an address field.
   *
   * @param \Drupal\address\AddressInterface $address
   *   The address id.
   * @param string $country
   *   The country code.
   * @param string $administrative_area
   *   The administrative area.
   * @param string $locality
   *   The locality.
   * @param string $dependent_locality
   *   The dependent locality.
   * @param string $postal_code
   *   The postal code.
   * @param string $sorting_code
   *   The sorting code.
   * @param string $address_line_1
   *   Address line 1.
   * @param string $address_line_2
   *   Address line 2.
   * @param string $given_name
   *   The given name.
   * @param string $additional_name
   *   Any additional names.
   * @param string $family_name
   *   The family name.
   * @param string $organization
   *   The organization string.
   */
  public function assertAddressItem(AddressInterface $address, $country, $administrative_area, $locality, $dependent_locality, $postal_code, $sorting_code, $address_line_1, $address_line_2, $given_name, $additional_name, $family_name, $organization) {
    $this->assertInstanceOf(AddressItem::class, $address);
    $this->assertSame($country, $address->getCountryCode());
    $this->assertSame($administrative_area, $address->getAdministrativeArea());
    $this->assertSame($locality, $address->getLocality());
    $this->assertSame($dependent_locality, $address->getDependentLocality());
    $this->assertSame($postal_code, $address->getPostalCode());
    $this->assertSame($sorting_code, $address->getSortingCode());
    $this->assertSame($address_line_1, $address->getAddressLine1());
    $this->assertSame($address_line_2, $address->getAddressLine2());
    $this->assertSame($given_name, $address->getGivenName());
    $this->assertSame($additional_name, $address->getAdditionalName());
    $this->assertSame($family_name, $address->getFamilyName());
    $this->assertSame($organization, $address->getOrganization());
  }

  /**
   * Assert an adjustment.
   *
   * @param \Drupal\commerce_order\Adjustment $expected
   *   The expected adjustment.
   * @param \Drupal\commerce_order\Adjustment $actual
   *   The actual adjustment.
   */
  public function assertAdjustment(Adjustment $expected, Adjustment $actual) {
    $this->assertSame($expected->getLabel(), $actual->getLabel());
    // Convert to a standard format before comparing.
    $formatted_number = $this->formatNumber($expected->getPercentage(), $actual->getPercentage());
    $this->assertSame($formatted_number['expected'], $formatted_number['actual']);
    $this->assertSame($expected->getSourceId(), $actual->getSourceId());
    $this->assertSame($expected->getType(), $actual->getType());
    $this->assertPrice($expected->getAmount(), $actual->getAmount());
  }

  /**
   * Assert multiple adjustments.
   *
   * @param \Drupal\commerce_order\Adjustment[] $expected_adjustments
   *   An array of expected adjustments.
   * @param \Drupal\commerce_order\Adjustment[] $actual_adjustments
   *   An array of actual adjustments.
   */
  public function assertAdjustments(array $expected_adjustments, array $actual_adjustments) {
    $this->assertSame(count($expected_adjustments), count($actual_adjustments));
    $i = 0;
    foreach ($expected_adjustments as $expected) {
      foreach ($actual_adjustments as $actual) {
        if (($expected->getLabel() === $actual->getLabel()) && ($expected->getType() === $actual->getType())) {
          $this->assertAdjustment($expected, $actual);
          $i++;
          break;
        }
      }
    }
    // Assert that every adjustment was tested.
    $this->assertSame(count($actual_adjustments), $i);
  }

  /**
   * Asserts a Currency entity.
   *
   * @param int $id
   *   The currency id.
   * @param string $currency_code
   *   The currency code.
   * @param string $name
   *   The name of the currency.
   * @param string $numeric_code
   *   The numeric code for the currency.
   * @param string $fraction_digits
   *   The number of fraction digits for this currency.
   * @param string $symbol
   *   The currency symbol.
   */
  public function assertCurrencyEntity($id, $currency_code, $name, $numeric_code, $fraction_digits, $symbol) {
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
    $currency = Currency::load($id);
    $this->assertInstanceOf(CurrencyInterface::class, $currency);
    $this->assertSame($currency_code, $currency->getCurrencyCode());
    $this->assertSame($name, $currency->getName());
    $this->assertSame($fraction_digits, $currency->getFractionDigits());
    $this->assertSame($numeric_code, $currency->getNumericCode());
    $this->assertSame($symbol, $currency->getSymbol());
  }

  /**
   * Assert a default store exists.
   */
  public function assertDefaultStore() {
    $defaultStore = $this->container->get('commerce_store.default_store_resolver')
      ->resolve();
    $this->assertInstanceOf(Store::class, $defaultStore);
  }

  /**
   * Asserts an order entity.
   *
   * @param array $order
   *   An array of order information.
   *   - id: The order id.
   *   - type: The order type.
   *   - number: The order number.
   *   - store_id: The store id.
   *   - created_time: The time the order was created.
   *   - changed_time:  The time the order was changed.
   *   - email: The email address for this order.
   *   - label: The label for this order.
   *   - ip_address: The ip address used to create this order.
   *   - customer_id: The customer id.
   *   - placed_time: The time the order was placed.
   *   - total_price_currency: Currency code for the total price.
   *   - total_price: The amount of the total price.
   *   - adjustments: An array of adjustments.
   *   - label_value: The state label
   *   - billing_profile: An array of billing profile target id and target
   *   revision id.
   *   - data: The data blob for this order.
   *   - order_items_ids: An array of order item IDs for this order.
   */
  public function assertOrder(array $order) {
    $order_instance = Order::load($order['id']);
    $this->assertInstanceOf(Order::class, $order_instance);
    $this->assertSame($order['type'], $order_instance->bundle());
    $this->assertSame($order['number'], $order_instance->getOrderNumber());
    $this->assertSame($order['store_id'], $order_instance->getStoreId());
    $this->assertSame($order['created_time'], $order_instance->getCreatedTime());
    $this->assertSame($order['changed_time'], $order_instance->getChangedTime());
    $this->assertSame($order['completed_time'], $order_instance->getCompletedTime());
    $this->assertSame($order['email'], $order_instance->getEmail());
    $this->assertInstanceOf(Profile::class, $order_instance->getBillingProfile());
    $this->assertSame($order['customer_id'], $order_instance->getCustomerId());
    $this->assertSame($order['ip_address'], $order_instance->getIpAddress());
    $this->assertSame($order['placed_time'], $order_instance->getPlacedTime());

    // Order total price may be null if the source order was incomplete.
    $actual_total_price = $order_instance->getTotalPrice();
    if ($actual_total_price != NULL) {
      $this->assertEquals($order['total_price_currency'], $order_instance->getTotalPrice()
        ->getCurrencyCode());
      $formatted_number = $this->formatNumber($order['total_price'], $order_instance->getTotalPrice()
        ->getNumber());
      $this->assertSame($formatted_number['expected'], $formatted_number['actual']);
    }

    $this->assertAdjustments($order['adjustments'], $order_instance->getAdjustments());
    $this->assertSame($order['label_value'], $order_instance->getState()->value);
    $data = $order_instance->get('data')->getValue();
    $this->assertSame($order['data'], $data);
    $state_label = $order_instance->getState()->getLabel();
    $label = NULL;
    if (is_string($state_label)) {
      $label = $state_label;
    }
    elseif ($state_label instanceof TranslatableMarkup) {
      $arguments = $state_label->getArguments();
      $label = isset($arguments['@label']) ? $arguments['@label'] : $state_label->render();
    }
    $this->assertSame($order['label_rendered'], $label);
    // Allow orders to be tested without a cart.
    if (isset($order['cart'])) {
      $this->assertSame($order['cart'], $order_instance->get('cart')->value);
    }

    // Test billing profile.
    $billing_profile = [
      'target_id' => $order['billing_profile'][0],
      'target_revision_id' => $order['billing_profile'][1],
    ];
    $this->assertSame([$billing_profile], $order_instance->get('billing_profile')
      ->getValue());

    // Test the order items as linked.
    $actual_order_items = $order_instance->get('order_items')->getValue();
    $actual_order_item_ids = [];
    foreach ($actual_order_items as $actual_order_item) {
      $actual_order_item_ids[] = $actual_order_item['target_id'];
    }
    sort($actual_order_item_ids);
    sort($order['order_items_ids']);
    $this->assertSame($order['order_items_ids'], $actual_order_item_ids);
  }

  /**
   * Asserts an order item.
   *
   * @param array $order_item
   *   An array of order item information.
   *   - order_item_id: The order item id.
   *   - purchased_entity_id: The id of the purchased entity.
   *   - created: The time the order item was created.
   *   - changed:  The time the order item was changed.
   *   - quantity: The order quantity.
   *   - title: The title of the item.
   *   - unit_price: The unit price of the item.
   *   - unit_price_currency_code: The unit price currency code.
   *   - total_price: The total price of this item.
   *   - total_price_currency_code: The total price currency code.
   *   - uses_legacy_adjustments: Set if the line item uses legacy adjustment
   *   calculation.
   *   - adjustments: An array of adjustments to this order item.
   */
  public function assertOrderItem(array $order_item) {
    $actual = OrderItem::load($order_item['id']);
    $this->assertInstanceOf(OrderItem::class, $actual);
    $this->assertSame($order_item['created'], $actual->getCreatedTime());
    $this->assertSame($order_item['changed'], $actual->getChangedTime());
    $formatted_number = $this->formatNumber($order_item['quantity'], $actual->getQuantity(), '%01.2f');
    $this->assertSame($formatted_number['expected'], $formatted_number['actual']);
    $this->assertEquals($order_item['title'], $actual->getTitle());
    $formatted_number = $this->formatNumber($order_item['unit_price'], $actual->getUnitPrice()
      ->getNumber());
    $this->assertSame($formatted_number['expected'], $formatted_number['actual']);
    $this->assertEquals($order_item['unit_price_currency_code'], $actual->getUnitPrice()
      ->getCurrencyCode());
    $formatted_number = $this->formatNumber($order_item['total_price'], $actual->getTotalPrice()
      ->getNumber());
    $this->assertSame($formatted_number['expected'], $formatted_number['actual']);
    $this->assertEquals($order_item['total_price_currency_code'], $actual->getTotalPrice()
      ->getCurrencyCode());
    $this->assertEquals($order_item['purchased_entity_id'], $actual->getPurchasedEntityId());
    $this->assertEquals($order_item['order_id'], $actual->getOrderId());
    $this->assertSame($order_item['uses_legacy_adjustments'], $actual->usesLegacyAdjustments());
    $this->assertAdjustments($order_item['adjustments'], $actual->getAdjustments());
  }

  /**
   * Asserts an order item type configuration entity.
   *
   * @param array $expected
   *   An array of order item type information.
   *   - The order item type.
   *   - The label for this order item type.
   *   - The purchasable EntityType.
   *   - The orderType.
   */
  public function assertOrderItemType(array $expected) {
    $order_item_type = OrderItemType::load($expected['id']);
    $this->assertInstanceOf(OrderItemType::class, $order_item_type);
    $this->assertSame($expected['label'], $order_item_type->label());
    $this->assertSame($expected['purchasableEntityType'], $order_item_type->getPurchasableEntityTypeId());
    $this->assertSame($expected['orderType'], $order_item_type->getOrderTypeId());
  }

  /**
   * Asserts a payment entity.
   *
   * @param array $payment
   *   An array of payment information.
   *   - The payment id.
   *   - The order id for this payment.
   *   - The payment type.
   *   - The gateway id.
   *   - The payment method.
   *   - The payment amount.
   *   - The payment currency code.
   *   - The order balance.
   *   - The order balance currency code.
   *   - The refunded amount.
   *   - The refunded amount currency code.
   */
  private function assertPaymentEntity(array $payment) {
    $payment_instance = Payment::load($payment['id']);
    $this->assertInstanceOf(Payment::class, $payment_instance);
    $this->assertSame($payment['order_id'], $payment_instance->getOrderId());
    $this->assertSame($payment['type'], $payment_instance->getType()
      ->getPluginId());
    $this->assertSame($payment['payment_gateway'], $payment_instance->getPaymentGatewayId());
    $this->assertSame($payment['payment_method'], $payment_instance->getPaymentMethodId());
    $formatted_number = $this->formatNumber($payment['amount_number'], $payment_instance->getAmount()
      ->getNumber());
    $this->assertSame($formatted_number['expected'], $formatted_number['actual']);
    $this->assertSame($payment['amount_currency_code'], $payment_instance->getAmount()
      ->getCurrencyCode());
    $formatted_number = $this->formatNumber($payment['balance_number'], $payment_instance->getBalance()
      ->getNumber(), '%01.2f');
    $this->assertSame($formatted_number['expected'], $formatted_number['actual']);
    $this->assertSame($payment['balance_currency_code'], $payment_instance->getBalance()
      ->getCurrencyCode());
    $formatted_number = $this->formatNumber($payment['refunded_amount_number'], $payment_instance->getRefundedAmount()
      ->getNumber());
    $this->assertSame($formatted_number['expected'], $formatted_number['actual']);
    $this->assertSame($payment['refunded_amount_currency_code'], $payment_instance->getRefundedAmount()
      ->getCurrencyCode());
    $this->assertSame($payment['label_value'], $payment_instance->getState()->value);
    $state_label = $payment_instance->getState()->getLabel();
    $label = NULL;
    if (is_string($state_label)) {
      $label = $state_label;
    }
    elseif ($state_label instanceof TranslatableMarkup) {
      $arguments = $state_label->getArguments();
      $label = isset($arguments['@label']) ? $arguments['@label'] : $state_label->render();
    }
    $this->assertSame($payment['label_rendered'], $label);

  }

  /**
   * Asserts a payment gateway entity.
   *
   * @param string $id
   *   The payment gateway id.
   * @param string $label
   *   The payment gateway label.
   * @param int $weight
   *   The payment gateway weight.
   */
  private function assertPaymentGatewayEntity($id, $label, $weight) {
    $gateway = PaymentGateway::load($id);
    $this->assertInstanceOf(PaymentGateway::class, $gateway);
    $this->assertSame($label, $gateway->label());
    $this->assertSame($weight, $gateway->getWeight());
  }

  /**
   * Assert a price.
   *
   * @param \Drupal\commerce_price\Price $expected
   *   The expected price.
   * @param \Drupal\commerce_price\Price $actual
   *   The actual price.
   */
  public function assertPrice(Price $expected, Price $actual) {
    $formatted_number = $this->formatNumber($expected->getNumber(), $actual->getNumber());
    $this->assertSame($formatted_number['expected'], $formatted_number['actual']);
    $this->assertSame($expected->getCurrencyCode(), $actual->getCurrencyCode());
  }

  /**
   * Asserts a product attribute entity.
   *
   * @param string $id
   *   The attribute id.
   * @param string $label
   *   The expected attribute label.
   * @param string $element_type
   *   The expected element type of the attribute.
   */
  protected function assertProductAttributeEntity($id, $label, $element_type) {
    $attribute = ProductAttribute::load($id);
    $this->assertInstanceOf(ProductAttribute::class, $attribute);
    $this->assertSame($label, $attribute->label());
    $this->assertSame($element_type, $attribute->getElementType());
  }

  /**
   * Asserts a product attribute value entity.
   *
   * @param string $id
   *   The attribute value id.
   * @param string $attribute_id
   *   The expected product attribute value id.
   * @param string $name
   *   The expected name of the product attribute value.
   * @param string $label
   *   The expected label of the product attribute value.
   * @param string $weight
   *   The expected weight of the product attribute value.
   */
  protected function assertProductAttributeValueEntity($id, $attribute_id, $name, $label, $weight) {
    $attribute_value = ProductAttributeValue::load($id);
    $this->assertInstanceOf(ProductAttributeValue::class, $attribute_value);
    $this->assertSame($attribute_id, $attribute_value->getAttributeId());
    $this->assertSame($name, $attribute_value->getName());
    $this->assertSame($label, $attribute_value->label());
    $this->assertSame($weight, $attribute_value->getWeight());
  }

  /**
   * Asserts a product.
   *
   * @param int $id
   *   The product id.
   * @param string $type
   *   The product bundle.
   * @param int $owner_id
   *   The uid for this billing profile.
   * @param string $title
   *   The title of the product.
   * @param string $is_published
   *   The published status of the product.
   * @param array $store_ids
   *   The ids of the stores for this product.
   * @param array $variations
   *   The variation of this product.
   */
  public function assertProductEntity($id, $type, $owner_id, $title, $is_published, array $store_ids, array $variations) {
    $product = Product::load($id);
    $this->assertInstanceOf(Product::class, $product);
    $this->assertSame($type, $product->bundle());
    $this->assertSame($owner_id, $product->getOwnerId());
    $this->assertSame($title, $product->getTitle());
    $this->assertSame($is_published, $product->isPublished());
    $this->assertSame($store_ids, $product->getStoreIds());
    // The variations may not be in the same order, sort them.
    $actual_variations = $product->getVariationIds();
    $this->assertSame(asort($variations), asort($actual_variations));
  }

  /**
   * Asserts a product type entity.
   *
   * @param string $id
   *   The product type id.
   * @param string $label
   *   The expected label.
   * @param string $description
   *   The expected description.
   * @param string $variation_type_id
   *   The expected product variation type id.
   */
  public function assertProductTypeEntity($id, $label, $description, $variation_type_id) {
    $product_type = ProductType::load($id);
    $this->assertInstanceOf(ProductType::class, $product_type);
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $field_definitions = $entity_field_manager->getFieldDefinitions('commerce_product', $id);
    $this->assertArrayHasKey('stores', $field_definitions);
    $this->assertArrayHasKey('body', $field_definitions);
    $this->assertArrayHasKey('variations', $field_definitions);
    $this->assertSame($label, $product_type->label());
    $this->assertSame($description, $product_type->getDescription());
    $this->assertSame($variation_type_id, $product_type->getVariationTypeId());
  }

  /**
   * Asserts a product variation.
   *
   * @param int $id
   *   The product variation id.
   * @param string $type
   *   The product variation bundle.
   * @param int $owner_id
   *   The uid for this billing profile.
   * @param string $sku
   *   The SKU.
   * @param string $price_number
   *   The price.
   * @param string $price_currency
   *   The currency code.
   * @param string $product_id
   *   The id of the product.
   * @param string $title
   *   The title.
   * @param string $order_item_type_id
   *   The order item type.
   * @param string $created_time
   *   The title.
   * @param string $changed_time
   *   The order item type.
   */
  public function assertProductVariationEntity($id, $type, $owner_id, $sku, $price_number, $price_currency, $product_id, $title, $order_item_type_id, $created_time, $changed_time) {
    $variation = ProductVariation::load($id);
    $this->assertInstanceOf(ProductVariation::class, $variation);
    $this->assertSame($type, $variation->bundle());
    $this->assertSame($owner_id, $variation->getOwnerId());
    $this->assertSame($sku, $variation->getSku());
    $formatted_number = $this->formatNumber($price_number, $variation->getPrice()
      ->getNumber());
    $this->assertSame($formatted_number['expected'], $formatted_number['actual']);
    $this->assertSame($price_currency, $variation->getPrice()
      ->getCurrencyCode());
    $this->assertSame($product_id, $variation->getProductId());
    $this->assertSame($title, $variation->getOrderItemTitle());
    $this->assertSame($order_item_type_id, $variation->getOrderItemTypeId());
    if ($created_time != NULL) {
      $this->assertSame($created_time, $variation->getCreatedTime());
    }
    if ($changed_time != NULL) {
      $this->assertSame($changed_time, $variation->getChangedTime());
    }
  }

  /**
   * Asserts a product variation type.
   *
   * @param string $id
   *   The product variation type.
   * @param string $label
   *   The expected label.
   * @param string $order_item_type_id
   *   The expected order item type id.
   * @param bool $is_title_generated
   *   The expected indicator that a title is generated.
   * @param array $traits
   *   An array of traits.
   */
  public function assertProductVariationTypeEntity($id, $label, $order_item_type_id, $is_title_generated, array $traits) {
    $variation_type = ProductVariationType::load($id);
    $this->assertInstanceOf(ProductVariationType::class, $variation_type);
    $this->assertSame($label, $variation_type->label());
    $this->assertSame($order_item_type_id, $variation_type->getOrderItemTypeId());
    $this->assertSame($is_title_generated, $variation_type->shouldGenerateTitle());
    $this->assertSame($traits, $variation_type->getTraits());
  }

  /**
   * Asserts a profile entity.
   *
   * @param int $id
   *   The profile id.
   * @param string $type
   *   The profile bundle.
   * @param int $owner_id
   *   The uid for this billing profile.
   * @param string $langcode
   *   The profile language code.
   * @param string $is_active
   *   The active state of the profile.
   * @param bool $is_default
   *   True if this this the default profile.
   * @param string $created_time
   *   The time the profile was created..
   * @param string $changed_time
   *   The time the profile was last changed.
   */
  public function assertProfile($id, $type, $owner_id, $langcode, $is_active, $is_default, $created_time, $changed_time) {
    $profile = Profile::load($id);
    $this->assertProfileEntity($profile, $type, $owner_id, $langcode, $is_active, $is_default, $created_time, $changed_time);
  }

  /**
   * Asserts a profile.
   *
   * @param int $profile
   *   The profile entity.
   * @param string $type
   *   The profile type.
   * @param int $owner_id
   *   The uid for this billing profile.
   * @param string $langcode
   *   The profile language code.
   * @param string $is_active
   *   The active state of the profile.
   * @param bool $is_default
   *   True if this this the default profile.
   * @param string $created_time
   *   The time the profile was created..
   * @param string $changed_time
   *   The time the profile was last changed.
   */
  public function assertProfileEntity($profile, $type, $owner_id, $langcode, $is_active, $is_default, $created_time, $changed_time) {
    $this->assertInstanceOf(Profile::class, $profile);
    $this->assertSame($type, $profile->bundle());
    $this->assertSame($owner_id, $profile->getOwnerId());
    $this->assertSame($langcode, $profile->language()->getId());
    $this->assertSame($is_active, $profile->isActive());
    $this->assertSame($is_default, $profile->isDefault());
    if ($created_time != NULL) {
      $this->assertSame($created_time, ($profile->getCreatedTime()));
    }
    if ($changed_time != NULL) {
      $this->assertSame($changed_time, $profile->getChangedTime());
    }
  }

  /**
   * Asserts a profile revision.
   *
   * @param int $id
   *   The profile id.
   * @param string $type
   *   The profile type.
   * @param int $owner_id
   *   The uid for this billing profile.
   * @param string $langcode
   *   The profile language code.
   * @param string $is_active
   *   The active state of the profile.
   * @param bool $is_default
   *   True if this this the default profile.
   * @param string $created_time
   *   The time the profile was created..
   * @param string $changed_time
   *   The time the profile was last changed.
   */
  public function assertProfileRevision($id, $type, $owner_id, $langcode, $is_active, $is_default, $created_time, $changed_time) {
    $revision = \Drupal::entityTypeManager()->getStorage('profile')
      ->loadRevision($id);
    $this->assertProfileEntity($revision, $type, $owner_id, $langcode, $is_active, $is_default, $created_time, $changed_time);
  }

  /**
   * Asserts a profile type configuration entity.
   *
   * @param string $id
   *   The profile id.
   * @param string $label
   *   The label for this profile.
   * @param bool $multiple
   *   Set if this profile can have multiples.
   * @param bool $revisions
   *   Set if this profile has revision.
   */
  public function assertProfileType($id, $label, $multiple, $revisions) {
    $profile_type = ProfileType::load($id);
    $this->assertInstanceOf(ProfileType::class, $profile_type);
    $this->assertSame($label, $profile_type->label());
    $this->assertSame($multiple, $profile_type->getMultiple());
    $this->assertSame($revisions, $profile_type->shouldCreateNewRevision());
  }

  /**
   * Asserts a shipping method.
   *
   * @param array $shipping_method
   *   An array of shipment type information.
   *   - id: The shipment id.
   *   - label: The label for the shipment type.
   *   - rate_amount: An array of the rate amount and the currency code, indexed
   *     by 'rate_amount' and 'currency code'.
   *   - store: an array of store ids that use this shipping method.
   */
  public function assertShippingMethod(array $shipping_method) {
    $shipping_method_instance = ShippingMethod::load($shipping_method['id']);
    $this->assertInstanceOf(ShippingMethod::class, $shipping_method_instance);
    $plugin = $shipping_method_instance->getPlugin();
    $this->assertSame($shipping_method['label'], $shipping_method_instance->label());
    $this->assertSame($shipping_method['stores'], $shipping_method_instance->getStoreIds());
    $rate_amount = [
      'number' => $shipping_method['rate_amount']['number'],
      'currency_code' => $shipping_method['rate_amount']['currency_code'],
    ];
    $this->assertEquals($rate_amount, $plugin->getConfiguration()['rate_amount']);
  }

  /**
   * Asserts a store entity.
   *
   * @param int $id
   *   The store id.
   * @param string $name
   *   The name of the store.
   * @param string $email
   *   The email address of the store.
   * @param string $default_currency_code
   *   The default currency code of the store.
   * @param string $bundle
   *   The bundle.
   * @param string $owner_id
   *   The owner id.
   */
  public function assertStoreEntity($id, $name, $email, $default_currency_code, $bundle, $owner_id) {
    $store = Store::load($id);
    $this->assertInstanceOf(Store::class, $store);
    $this->assertSame($name, $store->getName());
    $this->assertSame($email, $store->getEmail());
    $this->assertSame($default_currency_code, $store->getDefaultCurrencyCode());
    $this->assertSame($bundle, $store->bundle());
    $this->assertSame($owner_id, $store->getOwnerId());
  }

  /**
   * Asserts a tax type.
   *
   * @param int $id
   *   The TaxType id.
   * @param string $label
   *   The label for the TaxType.
   * @param string $plugin
   *   The TaxType plugin.
   * @param string $rate
   *   The TaxType rate.
   * @param array $territories
   *   The territories this tax type is applied to.
   */
  public function assertTaxType($id, $label, $plugin, $rate, array $territories) {
    $tax_type = TaxType::load($id);
    $this->assertInstanceOf(TaxType::class, $tax_type);
    $this->assertSame($label, $tax_type->label());
    $this->assertSame($plugin, $tax_type->getPluginId());

    $tax_type_config = $tax_type->getPluginConfiguration();
    $this->assertSame($id, $tax_type_config['rates'][0]['id']);
    $this->assertSame($label, $tax_type_config['rates'][0]['label']);
    $this->assertSame($rate, $tax_type_config['rates'][0]['percentage']);
    $this->assertSame($territories, $tax_type_config['territories']);
  }

  /**
   * Asserts an order entity.
   *
   * @param array $order
   *   An array of order information.
   *   - id: The order id.
   *   - type: The order type.
   *   - number: The order number.
   *   - store_id: The store id.
   *   - created_time: The time the order was created.
   *   - changed_time:  The time the order was changed.
   *   - email: The email address for this order.
   *   - label: The label for this order.
   *   - ip_address: The ip address used to create this order.
   *   - customer_id: The customer id.
   *   - placed_time: The time the order was placed.
   *   - total_price_currency: Currency code for the total price.
   *   - total_price: The amount of the total price.
   *   - adjustments: An array of adjustments.
   *   - label_value: The state label
   *   - billing_profile: An array of billing profile target id and target
   *   revision id.
   *   - data: The data blob for this order.
   *   - order_items_ids: An array of order item IDs for this order.
   *   - order_admin_comments: An array of order admin comments.
   *   - order_items_ids: An array of order comments.
   */
  public function assertUbercartOrder(array $order) {
    $this->assertOrder($order);
    $order_instance = Order::load($order['id']);
    // Only test if the expected array has data for the following fields. These
    // fields can have many entries and just gets unwieldy to create the correct
    // expected data.
    if (isset($order['order_admin_comments'])) {
      $this->assertSame($order['order_admin_comments'], $order_instance->get('field_order_admin_comments')
        ->getValue());
    }
    if (isset($order['order_comments'])) {
      $this->assertSame($order['order_comments'], $order_instance->get('field_order_comments')
        ->getValue());
    }
    if (isset($order['order_logs'])) {
      $this->assertSame($order['order_logs'], $order_instance->get('field_order_logs')
        ->getValue());
    }
  }

  /**
   * Creates a default store.
   */
  protected function createDefaultStore() {
    $currency_importer = \Drupal::service('commerce_price.currency_importer');
    /** @var \Drupal\commerce_store\StoreStorage $store_storage */
    $store_storage = \Drupal::service('entity_type.manager')
      ->getStorage('commerce_store');

    $currency_importer->import('USD');
    $store_values = [
      'type' => 'default',
      'uid' => 1,
      'name' => 'Demo store',
      'mail' => 'admin@example.com',
      'address' => [
        'country_code' => 'US',
      ],
      'default_currency' => 'USD',
    ];

    /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
    $store = $store_storage->create($store_values);
    $store->save();
    $store_storage->markAsDefault($store);
  }

  /**
   * Helper to test a product and its variations.
   *
   * @param array $product
   *   Array of product and product variation data.
   */
  public function productTest(array $product) {
    $variation_ids = [];
    foreach ($product['variations'] as $variation) {
      $variation_ids[] = $variation['variation_id'];
    }
    $this->assertProductEntity($product['product_id'], $product['type'], $product['uid'], $product['title'], $product['published'], $product['store_ids'], $variation_ids);
    $this->productVariationTest($product);
  }

  /**
   * Helper to test a product is linked to its variations.
   *
   * @param array $product
   *   Product and product variation data.
   */
  public function productVariationTest(array $product) {
    // Test variations.
    $productInstance = Product::load($product['product_id']);
    foreach ($product['variations'] as $variation) {
      $found = FALSE;
      foreach ($productInstance->variations as $variationInstance) {
        if ($variation['variation_id'] == $variationInstance->target_id) {
          $found = TRUE;
        }
      }
      $this->assertTrue($found, "No variation exists for variation_id: {$variation['variation_id']}");
      $this->assertProductVariationEntity($variation['variation_id'], $variation['uid'], $variation['sku'], $variation['price'], $variation['currency'], $product['product_id'], $variation['title'], $variation['order_item_type'], $variation['created_time'], $variation['changed_time']);
    }
  }

  /**
   * Formats a price number.
   *
   * @param string $expected
   *   The expected result number to format.
   * @param string $actual
   *   The actual result number to format.
   * @param string $format_string
   *   The format to convert the number to.
   *
   * @return array
   *   An associative array of the formatted numbers, 'expected' for the
   *   expected value and 'actual' for the actual value.
   */
  public function formatNumber($expected, $actual, $format_string = '%01.6f') {
    $ret['expected'] = $expected;
    $ret['actual'] = $actual;
    if ($this->container->get('database')->driver() === 'sqlite') {
      // SQLite does not support scales for float data types so we need to
      // convert the value manually.
      $ret['expected'] = sprintf($format_string, $expected);
      $ret['actual'] = sprintf($format_string, $actual);
    }
    return $ret;
  }

}
