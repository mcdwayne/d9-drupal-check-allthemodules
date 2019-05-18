<?php

namespace Drupal\commerce_opp\PluginForm;

use Drupal\commerce_opp\PaymentTypes;
use Drupal\commerce_opp\Plugin\Commerce\PaymentGateway\CopyAndPayBankAccountInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the COPYandPAY plugin form.
 */
class CopyAndPayForm extends PaymentOffsiteForm implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The number formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface
   */
  protected $numberFormatter;

  /**
   * Constructs a new CopyAndPayForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\commerce_price\NumberFormatterFactoryInterface $number_formatter_factory
   *   The number formatter factory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, NumberFormatterFactoryInterface $number_formatter_factory) {
    $this->currencyStorage = $entity_type_manager->getStorage('commerce_currency');
    $this->languageManager = $language_manager;
    $this->numberFormatter = $number_formatter_factory->createInstance();
    $this->numberFormatter->setMaximumFractionDigits(2);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('commerce_price.number_formatter_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();

    /** @var \Drupal\commerce_opp\Plugin\Commerce\PaymentGateway\CopyAndPayInterface $opp_gateway */
    $opp_gateway = $payment->getPaymentGateway()->getPlugin();
    $brands = $opp_gateway->getBrandIds();

    $payment_amount = $opp_gateway->getPayableAmount($order);
    $payment->setAmount($payment_amount);
    // Save to get an ID.
    $payment->save();

    $request_params = [
      'currency' => $payment_amount->getCurrencyCode(),
      'amount' => $payment_amount->getNumber(),
      'paymentType' => PaymentTypes::DEBIT,
      'descriptor' => $this->t('Order ID @order_id', ['@order_id' => $order->id()]),
      'customer.email' => $order->getEmail(),
      'customer.ip' => $order->getIpAddress(),
      'merchantInvoiceId' => $order->id(),
      'merchantTransactionId' => $payment->id(),
    ];

    $customer = $order->getCustomer();
    if ($customer && $customer->isAuthenticated()) {
      $request_params['customer.merchantCustomerId'] = $customer->id();
    }

    $billing_profile = $order->getBillingProfile();
    /** @var \Drupal\address\AddressInterface|null $billing_address */
    $billing_address = $billing_profile
      && $billing_profile->hasField('address')
      && !$billing_profile->get('address')->isEmpty() ?
        $billing_profile->address->first() : NULL;

    if ($billing_address) {
      $request_params['customer.givenName'] = $billing_address->getGivenName();
      $request_params['customer.surname'] = $billing_address->getFamilyName();

      if ($company = $billing_address->getOrganization()) {
        $request_params['customer.companyName'] = $company;
      }

      $request_params['billing.street1'] = $billing_address->getAddressLine1();
      $request_params['billing.street2'] = $billing_address->getAddressLine2();
      $request_params['billing.city'] = $billing_address->getLocality();
      $request_params['billing.postcode'] = $billing_address->getPostalCode();
      $request_params['billing.country'] = $billing_address->getCountryCode();
    }

    $shipping_profile = $this->getShippingProfile($order);
    /** @var \Drupal\address\AddressInterface|null $shipping_address */
    $shipping_address = $shipping_profile
    && $shipping_profile->hasField('address')
    && !$shipping_profile->get('address')->isEmpty() ?
      $shipping_profile->address->first() : NULL;

    if ($shipping_address) {
      $request_params['shipping.customer.email'] = $order->getEmail();
      $request_params['shipping.customer.ip'] = $order->getIpAddress();
      $request_params['shipping.customer.givenName'] = $shipping_address->getGivenName();
      $request_params['shipping.customer.surname'] = $shipping_address->getFamilyName();

      if ($company = $shipping_address->getOrganization()) {
        $request_params['shipping.customer.companyName'] = $company;
      }

      $request_params['shipping.street1'] = $shipping_address->getAddressLine1();
      $request_params['shipping.street2'] = $shipping_address->getAddressLine2();
      $request_params['shipping.city'] = $shipping_address->getLocality();
      $request_params['shipping.postcode'] = $shipping_address->getPostalCode();
      $request_params['shipping.country'] = $shipping_address->getCountryCode();
    }

    $checkout_id = $opp_gateway->prepareCheckout($request_params);

    // Set the checkout ID as (temporary) remote ID. On actual payment, we will
    // receive the real payment ID and we will use that as our remote ID then.
    $payment->setRemoteId($checkout_id);
    $payment->setExpiresTime($opp_gateway->calculateCheckoutIdExpireTime());
    $authorize_transition = $payment->getState()->getWorkflow()->getTransition('authorize');
    $payment->getState()->applyTransition($authorize_transition);
    $payment->save();

    $script_url = sprintf("%s/v1/paymentWidgets.js?checkoutId=%s", $opp_gateway->getActiveHostUrl(), $checkout_id);
    $js_settings = [
      'opp_script_url' => $script_url,
      'langcode' => $this->languageManager->getCurrentLanguage()->getId(),
    ];
    if ($opp_gateway instanceof CopyAndPayBankAccountInterface) {
      $sofort_countries = $opp_gateway->getSofortCountries();
      if ($opp_gateway->isSofortRestrictedToBillingAddress() && !empty($billing_address)) {
        $restrict_countries = [strtoupper($billing_address->getCountryCode())];
        $restrict_countries = array_combine($restrict_countries, $restrict_countries);
        $sofort_countries = array_intersect_key($sofort_countries, $restrict_countries);
      }
      $js_settings['sofort_countries'] = (object) $sofort_countries;
    }
    $form['#attached']['drupalSettings']['commerce_opp'] = $js_settings;
    $form['#attached']['library'][] = 'commerce_opp/init';

    $amount_formatted = '';
    if ($opp_gateway->isAmountVisible()) {
      /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
      $currency = $this->currencyStorage->load($payment_amount->getCurrencyCode());
      $amount_formatted = $this->t('Amount to be paid: @amount', ['@amount' => $this->numberFormatter->formatCurrency($payment_amount->getNumber(), $currency)]);
    }

    $form['cards'] = [
      '#type' => 'hidden',
      '#value' => implode(' ', $brands),
      // Plugin forms are embedded using #process, so it's too late to attach
      // another #process to $form itself, it must be on a sub-element.
      '#process' => [
        [get_class($this), 'processCopyAndPayForm'],
      ],
      '#action' => $form['#return_url'],
      '#cancel_url' => $form['#cancel_url'],
      '#amount' => $amount_formatted,
    ];

    // No need to call buildRedirectForm(), as we embed an iframe.
    return $form;
  }

  /**
   * Prepares the complete form in order to work with COPYandPAY.
   *
   * Sets the form #action, adds a class for the JS to target.
   * Workaround for buildConfigurationForm() not receiving $complete_form.
   *
   * @param array $element
   *   The form element whose value is being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed form element.
   */
  public static function processCopyAndPayForm(array $element, FormStateInterface $form_state, array &$complete_form) {
    $complete_form['#action'] = $element['#action'];
    $complete_form['#attributes']['class'][] = 'paymentWidgets';
    $complete_form['#attributes']['data-brands'] = $element['#value'];

    if (!empty($element['#amount'])) {
      $complete_form['#prefix'] = $element['#amount'];
    }

    // As the COPYandPAY fully replaces the HTML form, we need to place the
    // cancel link outside the form as suffix.
    $complete_form['#suffix'] = Link::fromTextAndUrl(t('Cancel'), Url::fromUri($element['#cancel_url']))->toString();

    return $element;
  }

  /**
   * Gets the shipping profile, if exists.
   *
   * The function safely checks for the existence of the 'shipments' field,
   * which is installed by commerce_shipping. If the field does not exist or is
   * empty, NULL will be returned.
   *
   * The shipping profile is assumed to be the same for all shipments.
   * Therefore, it is taken from the first found shipment, or created from
   * scratch if no shipments were found.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   The shipping profile.
   */
  protected function getShippingProfile(OrderInterface $order) {
    if ($order->hasField('shipments')) {
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
      foreach ($order->shipments->referencedEntities() as $shipment) {
        return $shipment->getShippingProfile();
      }
    }

    return NULL;
  }

}
