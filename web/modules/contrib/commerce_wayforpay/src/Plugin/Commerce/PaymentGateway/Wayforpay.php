<?php

namespace Drupal\commerce_wayforpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_wayforpay\Form\WayforpayFormTrait;
use Drupal\commerce_wayforpay\Form\WayforpayPaymentResponseForm;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment Liqapy gateway.
 *
 * @CommercePaymentGateway(
 *   id = "wayforpay",
 *   label = "Wayforpay (Off-site redirect)",
 *   display_label = "Wayforpay",
 *   forms = {
 *     "offsite-payment" =
 *   "Drupal\commerce_wayforpay\PluginForm\OffsiteRedirect\WayforpayPaymentForm",
 *   },
 *   modes = {"live"},
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa",
 *   },
 * )
 */
class Wayforpay extends OffsitePaymentGatewayBase {
  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  public $logger;

  /**
   * Constructs a new Wayforpay object.
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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    PaymentTypeManager $payment_type_manager,
    PaymentMethodTypeManager $payment_method_type_manager,
    TimeInterface $time,
    LoggerChannelFactoryInterface $logger
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition,
      $entity_type_manager, $payment_type_manager, $payment_method_type_manager,
      $time);
    $this->logger = $logger->get('commerce_wayforpay');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $domain = \Drupal::request()->getHost();
    $defaults = [
      'secretKey' => '',
      'merchantAccount' => '',
      'merchantDomainName' => $domain,
      'language' => 'AUTO',
      'returnUrl' => '',
      'serviceUrl' => '',
      'apiVersion' => 1,
      'holdTimeout' => 86400,
      'orderLifetime' => 86400,
    ];
    return $defaults + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
      array $form,
      FormStateInterface $form_state
  ) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['secretKey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key'),
      '#default_value' => $this->configuration['secretKey'],
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['merchantAccount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant account'),
      '#default_value' => $this->configuration['merchantAccount'],
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['merchantDomainName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant domain'),
      '#default_value' => $this->configuration['merchantDomainName'],
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['language'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Language'),
      '#default_value' => $this->configuration['language'],
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['apiVersion'] = [
      '#type' => 'Api version',
      '#title' => $this->t('Secret key'),
      '#default_value' => $this->configuration['apiVersion'],
      '#maxlength' => 1,
      '#required' => TRUE,
    ];
    $form['holdTimeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hold timeout'),
      '#default_value' => $this->configuration['holdTimeout'],
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['orderLifetime'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order life time'),
      '#default_value' => $this->configuration['orderLifetime'],
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(
      array &$form,
      FormStateInterface $form_state
  ) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration = array_merge($this->configuration, $values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $reasonCode = $request->get('reasonCode', '');
    $context = ['reasonCode' => $reasonCode, 'title' => 'Результат'];
    $payment_id = $request->get('orderReference', '');
    $context['payment_id'] = $payment_id;
    if ($payment_id) {
      $context['payment'] = $order;
    }
    if ($reasonCode != '1100') {
      $context = array_merge($context, WayforpayFormTrait::getErrorInfo($reasonCode));
      throw new PaymentGatewayException("{$context['reason']} {$context['message_for_client']}");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    $config = $this->getConfiguration();
    $body = file_get_contents('php://input');
    $logger = $this->logger;
    $logger->debug($body);
    $post_data = json_decode($body, TRUE);
    if (is_null($post_data)) {
      $post_data = $_POST;
    }
    $response_code = 200;

    $form = new WayforpayPaymentResponseForm($post_data, $config);
    $form->payment_gateway = $this->entityId;
    if ($form->isValid()) {
      $form->save();
      $current_date = time();
      $resp_data = [
        'orderReference' => $form->cleanedData['orderReference'],
        'status' => 'accept',
        'time' => $current_date,
      ];
      $resp_data['signature'] = $form->makeSignature($resp_data);
    }
    else {
      $resp_data = $form->errors;
      $response_code = 400;
    }
    $resp_data_string = json_encode($resp_data);
    $logger->debug('/billing/wayforpay/backref/ Response: ' . $resp_data_string);
    return new JsonResponse($resp_data, $response_code);
  }

  /**
   * Currency default validation.
   *
   * @param string $transaction_currency
   *   Currency from Wayforpay.
   * @param string $order_currency
   *   Currency from order.
   *
   * @return bool
   *   TRUE on successful validation FALSE otherwise.
   */
  protected function validateCurrency($transaction_currency, $order_currency) {
    if ($transaction_currency != $order_currency) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Amount default validation.
   *
   * @param float $transaction_amount
   *   Amount from Wayforpay.
   * @param float $order_amount
   *   Amount from order.
   *
   * @return bool
   *   TRUE on successful validation FALSE otherwise.
   */
  protected function validateAmount($transaction_amount, $order_amount) {
    if ($transaction_amount != $order_amount) {
      return FALSE;
    }
    return TRUE;
  }

}
