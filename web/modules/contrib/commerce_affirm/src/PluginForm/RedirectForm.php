<?php

namespace Drupal\commerce_affirm\PluginForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_promotion\Entity\Promotion;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_affirm\Event\AffirmTransactionDataPreSend;
use Drupal\commerce_affirm\Event\AffirmEvents;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;

class RedirectForm extends PaymentOffsiteForm {

  /**
   * The minor units converter service.
   *
   * @var \Drupal\commerce_affirm\MinorUnitsInterface
   */
  protected $minorUnits;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->minorUnits = \Drupal::service('commerce_affirm.minor_units');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase $plugin */
    $plugin = $this->plugin;

    $configuration = $plugin->getConfiguration();

    // Return an error if the enabling action's settings haven't been configured.
    if (empty($configuration['public_key']) || empty($configuration['financial_key'])) {
      drupal_set_message(t('Affirm is not configured for use. You must check your settings.'), 'error');
      return [];
    }
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->getEntity();
    $order = $payment->getOrder();
    $order_total = $order->getTotalPrice();

    $commerce_module_info = system_get_info('module', 'commerce');
    $affirm_module_info = system_get_info('module', 'commerce_affirm');
    if ($commerce_module_info['version'] == '') {
      $commerce_module_info['version'] = '8.x-1.??';
    }

    // Creates a data array to be passed onto the request.
    $data = [
      // General settings.
      'ApiMode'             => $plugin->getMode() === 'live' ? 'live' : 'sandbox',
      'PublicKey'           => $configuration['public_key'],
      'FinancialProductKey' => $configuration['financial_key'],
      'OrderId'             => $order->id(),
      'Email'               => $order->getEmail(),
      'CancelUrl'           => $form['#cancel_url'],
      'ConfirmUrl'          => $form['#return_url'],
      'items'               => [],
      'metadata'            => [
        'platform_type' => 'Drupal',
        'platform_version' => 'Drupal ' . \Drupal::VERSION . '; Drupal Commerce ' . $commerce_module_info['version'],
        'platform_affirm' => $affirm_module_info['version'],
        'shipping_type' => '',
        // This gets sent back by Affirm so that we can use it in onReturn().
        'capture' => $form['#capture'],
      ],
      'ShippingTotal'       => 0,
      'TaxAmount'           => 0,
      'ProductsTotal'       => $this->minorUnits->toMinorUnits($order_total),
    ];
    if ($configuration['window_mode'] == 'modal') {
      $data['metadata']['mode'] = 'modal';
    }

    // Adds information about the billing profile.
    if (!$order->getBillingProfile()->get('address')->isEmpty()) {
      /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billing_address */
      $billing_address = $order->getBillingProfile()->get('address')->first();

      $data += [
        'BillingFullName'        => $billing_address->getGivenName() . ' ' . $billing_address->getFamilyName(),
        'BillingFirstName'       => $billing_address->getGivenName(),
        'BillingLastName'        => $billing_address->getFamilyName(),
        'BillingAddressLn1'      => $billing_address->getAddressLine1(),
        'BillingAddressLn2'      => $billing_address->getAddressLine2(),
        'BillingAddressPostCode' => $billing_address->getPostalCode(),
        'BillingAddressState'    => $billing_address->getAdministrativeArea(),
        'BillingAddressCity'     => $billing_address->getLocality(),
        'BillingAddressCountry'  => $billing_address->getCountryCode(),
        'BillingTelephone'       => '',
      ];
    }

    // Use the shipping profile if the shipping module exists and the order
    // contain a shipping profile otherwise use the Billing profile.
    /** @var \Drupal\Core\Extension\ModuleHandler $module_handler */
    $module_handler = \Drupal::service('module_handler');
    $shipping_address = $module_handler->moduleExists('commerce_shipping') && $order->hasField('shipments') && !empty($order->shipments->first()->entity)
    ? $order->shipments->first()->entity->getShippingProfile()->get('address')->first()
    : $billing_address;

    $data += [
      'ShippingFullName'        => $shipping_address->getGivenName() . ' ' . $shipping_address->getFamilyName(),
      'ShippingFirstName'       => $shipping_address->getGivenName(),
      'ShippingLastName'        => $shipping_address->getFamilyName(),
      'ShippingAddressLn1'      => $shipping_address->getAddressLine1(),
      'ShippingAddressLn2'      => $shipping_address->getAddressLine2(),
      'ShippingAddressCountry'  => $shipping_address->getCountryCode(),
      'ShippingAddressPostCode' => $shipping_address->getPostalCode(),
      'ShippingAddressState'    => $shipping_address->getAdministrativeArea(),
      'ShippingAddressCity'     => $shipping_address->getLocality(),
      'ShippingTelephone'       => '',
    ];

    foreach ($order->getItems() as $order_item) {
      $data['items'][] = [
        'sku' => $order_item->getPurchasedEntity()->getSku(),
        'qty' => $order_item->getQuantity(),
        'display_name' => $order_item->getTitle(),
        'item_url' => $order_item->getPurchasedEntity()->toUrl(),
        'item_image_url' => '',
        'unit_price' => $this->minorUnits->toMinorUnits($order_item->getUnitPrice()),
      ];

      /** @var \Drupal\commerce_order\Adjustment $adjustment */
      foreach ($order_item->getAdjustments() as $adjustment) {
        $this->handleAdjustment($order, $adjustment, $data);
      }
    }

    foreach ($order->getAdjustments() as $adjustment) {
      $this->handleAdjustment($order, $adjustment, $data);
    }
    sort($data['items']);

    // Allow modules to alter the order data sent to Affirm before the request.
    /** @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher */
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event = new AffirmTransactionDataPreSend($data, $order);
    $event_dispatcher->dispatch(AffirmEvents::AFFIRM_TRANSACTION_DATA_PRESEND, $event);

    ksort($data);
    $form['#attached']['library'][] = 'commerce_affirm/affirm-checkout';

    // Pass checkout settings through to front-end.
    $form['#attached']['drupalSettings']['commerce_affirm'] = $data;

    if ($configuration['log']) {
      $vars = [
        '!function' => __FUNCTION__,
        '!data' => '<pre>' . print_r($data, TRUE) . '</pre>',
      ];
      \Drupal::logger('commerce_affirm')->warning(sttr('!function debug data:!data', $vars));
    }

    return $form;
  }

  /**
   * Turn adjustment data into the corresponding checkout object data.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_order\Adjustment $adjustment
   *   The adjustment.
   * @param array $data
   *   The Affirm checkout object data as an array.
   */
  protected function handleAdjustment(OrderInterface $order, Adjustment $adjustment, array &$data) {
    switch ($adjustment->getType()) {
      case 'promotion':
        $promotion = Promotion::load($adjustment->getSourceId());
        if ($promotion->hasCoupons()) {
          /** @var \Drupal\commerce_promotion\Entity\Coupon $coupon */
          foreach ($order->coupons->referencedEntities() as $coupon) {
            if ($coupon->getPromotionId() == $promotion->id()) {
              if (!isset($data['discounts'][$coupon->getCode()])) {
                $data['discounts'][$coupon->getCode()] = [
                  'discount_display_name' => $promotion->getName(),
                  'discount_amount' => '0',
                ];
                break;
              }
            }
          }
          $data['discounts'][$coupon->getCode()]['discount_amount'] = Calculator::add($data['discounts'][$coupon->getCode()]['discount_amount'], Calculator::multiply(-1, $this->minorUnits->toMinorUnits($adjustment->getAmount())));
        }
        else {
          if (!isset($data['discounts'][$promotion->id()])) {
            $data['discounts'][$promotion->id()] = [
              'discount_display_name' => $promotion->getName(),
              'discount_amount' => '0',
            ];
          }
          $data['discounts'][$promotion->id()]['discount_amount'] = Calculator::add($data['discounts'][$promotion->id()]['discount_amount'], Calculator::multiply(-1, $this->minorUnits->toMinorUnits($adjustment->getAmount())));
        }
        break;

      case 'shipping':
        if (!isset($data['ShippingTotal'])) {
          $data['ShippingTotal'] = '0';
        }
        $data['ShippingTotal'] = Calculator::add($data['ShippingTotal'], $this->minorUnits->toMinorUnits($adjustment->getAmount()));
        break;

      case 'tax':
        if (!isset($data['TaxAmount'])) {
          $data['TaxAmount'] = 0;
        }
        $data['TaxAmount'] = Calculator::add($data['TaxAmount'], $this->minorUnits->toMinorUnits($adjustment->getAmount()));
        break;
    }
  }

}
