<?php

namespace Drupal\commerce_paytabs\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PaytabsOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $config                 = $payment_gateway_plugin->getConfiguration();

    /** @var \Drupal\commerce_price\Price $amount */
    $payment_amount = $payment->getAmount();
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $payment->getOrder()->getBillingProfile();
    /** @var \Drupal\user\Entity\User $user */
    $user = $profile->getOwner();
    $language = \Drupal::languageManager()->getCurrentLanguage()->getName();
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billing_info */
    $billing_info = $profile->get('address')->first();
    /** @var \Drupal\telephone\Plugin\Field\FieldType\TelephoneItem $phone */
    $phone = $profile->get('telephone')->value;
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $payment->getOrder();


    $products                 = $this->getItemsTitleName($order);
    $product_titles           = implode(' || ', $products);

    $items_unit_price         = $this->getOrderItemsUnitPrice($order);
    $product_items_unit_price = implode(' || ', $items_unit_price);

    $items_quantity           = $this->getOrderItemsQuantity($order);
    $product_items_quantity   = implode(' || ', $items_quantity);


    $site_url      = Url::fromUri('internal:/', ['absolute' => TRUE])
                        ->toString();
    $site_ip       = gethostbyname($site_url);

    $order_number = $payment->getOrder()->id();
    $transaction_title = t('Order Number: ') . $order_number;


    $raw_amount    = $payment_amount->getNumber();
    $amount        = number_format($raw_amount, 3);
    $currency_code = $payment_amount->getCurrencyCode();
    $order_country_code = $billing_info->getCountryCode();
    $country = \Drupal::service('address.country_repository')->get($order_country_code)->getThreeLetterCode();

    $discount = $this->getPromotionsTotal($order);
    $other_charges = array_sum($this->getOtherCharges($order));

    $shipping_info = $payment_gateway_plugin->getShippingInfo($order);
    $shipping_first_name  = $shipping_info['shipping_first_name'];
    $shipping_last_name  = $shipping_info['shipping_last_name'];
    $address_shipping     = $shipping_info['address_shipping'];
    $city_shipping        = $shipping_info['city_shipping'];
    $state_shipping       = $shipping_info['state_shipping'];
    $postal_code_shipping = $shipping_info['postal_code_shipping'];
    $country_shipping     = $shipping_info['country_shipping'];

    $paytabs_data = [
      'merchant_email'       => $config['merchant_email'],
      'secret_key'           => $config['secret_key'],
      'site_url'             => $site_url,
      'return_url'           => $form['#return_url'],
      'title'                => $transaction_title,
      'cc_first_name'        => $billing_info->getGivenName(),
      'cc_last_name'         => $billing_info->getFamilyName(),
      'cc_phone_number'      => '00'.'973', // @TODO get country area code
      'phone_number'         => $phone, // @TODO phone field validation
      'email'                => $user->getEmail(),
      'products_per_title' => $product_titles,
      'unit_price' => $product_items_unit_price,
      'quantity' => $product_items_quantity,
      'other_charges'        => $other_charges,
      'amount'               => $amount,
      'discount'             => $discount,
      'currency'             => $currency_code,
      'reference_no'         => $order_number,
      'ip_customer'          => $order->getIpAddress(),
      'ip_merchant'          => $site_ip,
      'billing_address'      => $billing_info->getAddressLine1(),
      'city'                 => $billing_info->getLocality(),
      'state'                => $billing_info->getAdministrativeArea() ? $billing_info->getAdministrativeArea() : $billing_info->getLocality(),
      'postal_code'          => $billing_info->getPostalCode() ? $billing_info->getPostalCode() : '00000',
      'country'              => $country,
      'shipping_first_name'  => $shipping_first_name ? $shipping_first_name : $billing_info->getGivenName(),
      'shipping_last_name'   => $shipping_last_name ? $shipping_last_name : $billing_info->getFamilyName(),
      'address_shipping'     => $address_shipping ? $address_shipping : $billing_info->getAddressLine1(),
      'city_shipping'        => $city_shipping ? $city_shipping : $billing_info->getLocality(),
      'state_shipping'       => ($state_shipping ? $state_shipping : $city_shipping) ? $billing_info->getAdministrativeArea() : $billing_info->getLocality(),
      'postal_code_shipping' => $postal_code_shipping ? $postal_code_shipping : ($billing_info->getPostalCode() ? $billing_info->getPostalCode() : '00000'),
      'country_shipping'     => $country_shipping ? $country_shipping : $country,
      'msg_lang'             => $language,
      'cms_with_version'     => 'Drupal 8',
    ];

    $api_uri    = Url::fromUri('https://www.paytabs.com/apiv2/create_pay_page')->toString();

    $response = $payment_gateway_plugin->doHttpRequest($api_uri, $paytabs_data);

    if ($response->response_code == 4012) {
      $payment_url = $response->payment_url;
    }
    else {
      throw new PaymentGatewayException(sprintf('[Paytabs error #%s]: %s', $response->response_code, $response->result));
    }
    $redirect_url = $payment_url;
    $form['commerce_message']['#action'] = $redirect_url;
    $data = [
     // 'return' => $form['#return_url'],
    ];
    $redirect_method = 'post';

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
  }

  /**
   * Gets the unit price for each order item.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return array
   */
  protected function getOrderItemsUnitPrice(OrderInterface $order) {
    $order_item_unit = [];
    $order_items     = $order->getItems();
    foreach ($order_items as $order_item) {
      if (!empty($order_item)) {
        $order_item_unit[] = number_format($order_item->getUnitPrice()->getNumber(), 3);
      }
    }
    return $order_item_unit;
  }

  /**
   * Gets the quantity for each order item.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return array
   */
  protected function getOrderItemsQuantity(OrderInterface $order) {
    $order_item_quantity = [];
    $order_items         = $order->getItems();
    foreach ($order_items as $order_item) {
      if (!empty($order_item)) {
        $order_item_quantity[] = number_format($order_item->getQuantity());
      }
    }
    return $order_item_quantity;
  }

  /**
   * Gets the title for each order item.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return array
   */
  protected function getItemsTitleName(OrderInterface $order) {
    $order_item_title = [];
    $order_items      = $order->getItems();
    foreach ($order_items as $order_item) {
      if (!empty($order_item)) {
        $order_item_title[] = $order_item->getTitle();
      }
    }
    return $order_item_title;
  }

  /**
   * Get the discount out of the total adjustments
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return string
   */
  Protected function getPromotionsTotal(OrderInterface $order) {
    foreach ($order->collectAdjustments() as $adjustment) {
      $type = $adjustment->getType();
      if ($type == 'Promotion') {
        return $promotion = number_format($type->getAmount()->getNumber(), 3);
      }
    }
  }

  /**
   * Get the all other charges (e.g. shipping charges, taxes, VAT, etc) minus discounts
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *
   * @return array
   */
  Protected function getOtherCharges(OrderInterface $order) {
    $other_charges = [];
    foreach ($order->collectAdjustments() as $adjustment) {
      if ($adjustment->isPositive()) {
        $other_charges[] = number_format($adjustment->getAmount()->getNumber(), 3);
      }
    }
    return $other_charges;
  }

}
