<?php

namespace Drupal\commerce_tpay\PluginForm\TpayRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Provides the Off-site payment form.
 */
class TpayPaymentForm extends BasePaymentOffsiteForm implements ContainerInjectionInterface  {
  
  const TPAY_API_URL = 'https://secure.tpay.com';
  
  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;
  
  /**
   * TpayPaymentForm constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   */
  public function __construct(LanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    
    $redirect_method = 'post';
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $order = $form_state->getFormObject()->getOrder();
    
    $merchant_id = $payment_gateway_plugin->getConfiguration()['merchant_id'];
    $merchant_secret = $payment_gateway_plugin->getConfiguration()['merchant_secret'];

    $address = $order->getBillingProfile()->address->first();
    
    $price = $payment->getAmount()->getNumber();
    $currency = $payment->getAmount()->getCurrencyCode();
    
    $crc = $order->id() . '/' . $currency;
    
    $current_langcode = strtoupper($this->languageManager->getCurrentLanguage()->getId());
    $tpay_languages = ['DE', 'EN', 'PL'];
    $language = in_array($current_langcode, $tpay_languages) ? $current_langcode : 'EN';
    
    $parameters = [
      'id' => $merchant_id,
      'kwota' => $price,
      'opis' => t('Order no') . ' ' . $order->id(),
      'crc' => $crc,
      'md5sum' => md5($merchant_id . $price . $crc . $merchant_secret),
      'nazwisko' => $address->getGivenName() . ' ' . $address->getFamilyName(),
      'adres' => $address->getAddressLine1(),
      'city' => $address->getLocality(),
      'kraj' => $address->getCountryCode(),
      'email' => $order->getEmail(),
      'jezyk' => $language,
      'wyn_url' => $payment_gateway_plugin->getNotifyUrl(),
      'pow_url' => $payment_gateway_plugin->getReturnUrl($order),
      'pow_url_blad' => $payment_gateway_plugin->getCancelUrl($order),
    ];
    
    $redirect_url = self::TPAY_API_URL;
    
    return $this->buildRedirectForm($form, $form_state, $redirect_url, $parameters, $redirect_method);
  }
}
