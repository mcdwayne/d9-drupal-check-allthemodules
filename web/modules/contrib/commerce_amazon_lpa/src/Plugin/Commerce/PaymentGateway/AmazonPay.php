<?php

namespace Drupal\commerce_amazon_lpa\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_amazon_lpa\AmazonPay as AmazonPayApi;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Amazon Pay payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "amazon_pay",
 *   label = "Amazon Pay",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_amazon_lpa\PluginForm\PaymentMethodAddForm",
 *     "refund-payment" = "Drupal\commerce_amazon_lpa\PluginForm\PaymentRefundForm",
 *   },
 *   display_label = "Amazon Pay",
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "discover", "mastercard", "visa",
 *   },
 * )
 */
class AmazonPay extends PaymentGatewayBase implements AmazonPayInterface {

  /**
   * The Amazon Pay settings.
   *
   * @var array|\Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig|mixed|null
   */
  protected $amazonPaySettings;

  /**
   * The Amazon Pay API wrapper.
   *
   * @var \Drupal\commerce_amazon_lpa\AmazonPay
   */
  protected $amazonPay;

  /**
   * Constructs a new AmazonPay object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   *   The payment type manager.
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   *   The payment method type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory.
   * @param \Drupal\commerce_amazon_lpa\AmazonPay $amazon_pay
   *   The amazon pay service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, ConfigFactory $config, AmazonPayApi $amazon_pay) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->amazonPaySettings = $config->get('commerce_amazon_lpa.settings');
    $this->configuration['mode'] = $this->amazonPaySettings->get('mode') == 'test' ? 'test' : 'live';
    $this->amazonPay = $amazon_pay;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('config.factory'),
      $container->get('commerce_amazon_lpa.amazon_pay')
    );
  }

  /**
   * Creates a payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   */
  public function createPayment(PaymentInterface $payment) {
    $this->amazonPay->authorize($payment);
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->amazonPay->capture($payment, $amount);
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    // TODO: Implement onNotify() method.
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->amazonPay->refund($payment, $amount);
  }

  /**
   * Refund with seller notes.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to refund.
   * @param \Drupal\commerce_price\Price $amount
   *   The amount to refund.
   * @param string $notes
   *   The seller notes to send with the refund request.
   */
  public function refundPaymentWithNotes(PaymentInterface $payment, Price $amount = NULL, $notes = '') {
    $this->amazonPay->refund($payment, $amount, $notes);
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    // TODO: Implement voidPayment() method.
  }

}
