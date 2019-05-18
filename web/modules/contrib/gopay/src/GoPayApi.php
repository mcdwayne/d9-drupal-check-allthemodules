<?php

namespace Drupal\gopay;

use GoPay\Api;
use Drupal\Core\Config\ConfigFactoryInterface;
use GoPay\Definition\Payment\PaymentInstrument;
use Drupal\gopay\Payment\PaymentInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class GoPayApi.
 *
 * @package Drupal\gopay
 */
class GoPayApi implements GoPayApiInterface {
  use StringTranslationTrait;

  /**
   * ConfigFactory Service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Logger Service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * Payments object created on GoPay init.
   *
   * @var \GoPay\Payments
   */
  protected $goPay;

  /**
   * GoPayApi constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   ConfigFactory Service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactory $logger) {
    $this->configFactory = $config_factory;
    $this->logger = $logger;
    $settings = $this->configFactory->get('gopay.settings');

    $this->goPay = $this->config([
      'goid' => $settings->get('go_id'),
      'clientId' => $settings->get('client_id'),
      'clientSecret' => $settings->get('client_secret'),
      'isProductionMode' => $settings->get('production_mode'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function config(array $user_config, array $user_services = []) {
    return Api::payments($user_config, $user_services);
  }

  /**
   * {@inheritdoc}
   */
  public function runTests() {
    $token = $this->goPay->auth->authorize();

    return [
      'token' => $token,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentInstruments() {
    return [
      PaymentInstrument::PAYMENT_CARD => $this->t('Payment card'),
      PaymentInstrument::BANK_ACCOUNT => $this->t('Bank account'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildLink(PaymentInterface $payment, $text = NULL) {
    $config = $payment->toArray();
    $response = $this->goPay->createPayment($config);

    if (!$response->hasSucceed()) {
      $this->logger->get('gopay')->error('Unexpected error GoPay API error: ' . $response->__toString());

      return ['#markup' => $this->t('Unexpected GoPay API error.')];
    }

    if (!$text) {
      $text = $this->t('Pay');
    }

    return [
      '#theme' => 'gopay_link',
      '#link_text' => $text,
      '#gopay_url' => $response->json['gw_url'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildInlineForm(PaymentInterface $payment, $text = NULL) {
    $config = $payment->toArray();
    $response = $this->goPay->createPayment($config);

    if (!$response->hasSucceed()) {
      $this->logger->get('gopay')->error('Unexpected error GoPay API error: ' . $response->__toString());

      return ['#markup' => $this->t('Unexpected GoPay API error.')];
    }

    if (!$text) {
      $text = $this->t('Pay');
    }

    return [
      '#theme' => 'gopay_inline_form',
      '#link_text' => $text,
      '#gopay_url' => $response->json['gw_url'],
      '#embed_js' => $this->goPay->urlToEmbedJs(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentStatus($id) {
    return $this->goPay->getStatus($id);
  }

}
