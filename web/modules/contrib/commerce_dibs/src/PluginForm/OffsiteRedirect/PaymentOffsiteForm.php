<?php

namespace Drupal\commerce_dibs\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Off-site Redirect payment gateway form.
 */
class PaymentOffsiteForm extends BasePaymentOffsiteForm implements ContainerInjectionInterface {

  /**
   * DIBS payment window URL.
   */
  const REDIRECT_URL = 'https://sat1.dibspayment.com/dibspaymentwindow/entrypoint';

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * PaymentOffsiteForm constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, LanguageManagerInterface $language_manager) {
    $this->moduleHandler = $moduleHandler;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('module_handler'), $container->get('language_manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    // DIBS is settings the base amount value as cents.
    $amount = $payment->getAmount()->multiply("100")->getNumber();
    $currency_code = $payment->getAmount()->getCurrencyCode();
    $order_id = $payment->getOrderId();
    $order = $payment->getOrder();

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $plugin_configuration = $payment_gateway_plugin->getConfiguration();
    $redirect_url = static::REDIRECT_URL;
    $dibs_merchant_id = $plugin_configuration['merchant'];
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $pay_type = $plugin_configuration['pay_type'];
    $account = $plugin_configuration['account'];

    $data = [
      'acceptReturnUrl' => $form['#return_url'],
      'amount' => $amount,
      'currency' => $currency_code,
      'merchant' => $dibs_merchant_id,
      'orderId' => $order_id,
      'cancelReturnUrl' => $form['#cancel_url'],
      'language' => $language,
    ];

    if ($form['#capture']) {
      $data['captureNow'] = 1;
    }

    if (!empty($pay_type)) {
      $data['payType'] = $pay_type;
    }
    if (!empty($account)) {
      $data['account'] = $account;
    }
    if ($plugin_configuration['mode'] == 'test') {
      $data['test'] = 1;
    }

    // Allows other modules to alter the payment data sent to DIBS.
    $this->moduleHandler->alter('commerce_dibs_payment_data', $data, $order, $payment);

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, static::REDIRECT_POST);
  }

}
