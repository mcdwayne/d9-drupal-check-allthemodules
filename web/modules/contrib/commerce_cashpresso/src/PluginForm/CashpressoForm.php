<?php

namespace Drupal\commerce_cashpresso\PluginForm;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\commerce_price\RounderInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the offsite payment form for the cashpresso payment gateway.
 */
class CashpressoForm extends PaymentOffsiteForm implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The price rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * Constructs a new CashpressoForm object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   The price rounder.
   */
  public function __construct(LanguageManagerInterface $language_manager, RounderInterface $rounder) {
    $this->languageManager = $language_manager;
    $this->rounder = $rounder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('commerce_price.rounder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_cashpresso\Plugin\Commerce\PaymentGateway\CashpressoGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $order = $payment->getOrder();
    $amount = $this->rounder->round($payment->getAmount());
    /** @var \Drupal\address\AddressInterface $address */
    $address = $order->getBillingProfile()->address->first();

    $form['cashpresso_token'] = [
      '#type' => 'hidden',
      '#attributes' => ['id' => 'cashpressoToken'],
    ];

    $form['cashpresso_checkout'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => ['id' => 'cashpresso-checkout'],
    ];

    $js_settings = [
      'url' => 'https://my.cashpresso.com/ecommerce/v2/checkout/c2_ecom_checkout.all.min.js',
      'data' => [
        'partnerApiKey' => $payment_gateway_plugin->getApiKey(),
        'interestFreeDaysMerchant' => $payment_gateway_plugin->getInterestFreeDaysMerchant(),
        'mode' => $payment_gateway_plugin->getMode(),
        'locale' => $this->languageManager->getCurrentLanguage()->getId(),
        'amount' => $amount->getNumber(),
        'email' => $order->getEmail(),
        'given' => $address->getGivenName(),
        'family' => $address->getFamilyName(),
        'country' => strtolower($address->getCountryCode()),
        'city' => $address->getLocality(),
        'zip' => $address->getPostalCode(),
        'addressline' => $address->getAddressLine1(),
      ],
    ];

    $form['#attached']['drupalSettings']['commerce_cashpresso'] = $js_settings;
    $form['#attached']['library'][] = 'commerce_cashpresso/checkout';

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Accept instalments and complete purchase'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromUri($form['#cancel_url']),
    ];

    // No need to call buildRedirectForm(), as we embed an iframe.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    if (empty($values['cashpresso_token'])) {
      $form_state->setError($form['cashpresso_token'], $this->t('You have to choose instalments first.'));
      return;
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_cashpresso\Plugin\Commerce\PaymentGateway\CashpressoGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $cashpresso_token = $values['cashpresso_token'];
    $success = $payment_gateway_plugin->authorizePayment($payment, $cashpresso_token);
    if (!$success) {
      throw new NeedsRedirectException($form['#exception_url']);
    }
  }

}
