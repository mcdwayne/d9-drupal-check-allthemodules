<?php

namespace Drupal\commerce_payplug\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payplug\Services\PayPlugServiceInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the off-site PayPlug payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "offsite_payplug",
 *   label = "PayPlug (Off-site redirect)",
 *   display_label = "Payment via PayPlug",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_payplug\PluginForm\OffsitePayPlug\OffsitePayPlugForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "visa", "mastercard"
 *   },
 * )
 */
class OffsitePayPlug extends OffsitePaymentGatewayBase implements OffsitePayPlugInterface {

  /**
   * The PayPlug Service interface.
   *
   * @var \Drupal\commerce_payplug\Services\PayPlugServiceInterface
   */
  protected $payPlugService;

  /**
   * Constructs a new OffsitePayPlug object.
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
   * @param \Drupal\commerce_payplug\Service\PayplugServiceInterface
   *   The PayPlug payment service interface.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, PayPlugServiceInterface $payplug_service, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->payPlugService = $payplug_service;
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
      $container->get('commerce_payplug.payplug.service'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'live_apikey' => '',
        'test_apikey' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Input for Live API key.
    $form['live_apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Live API key'),
      '#description' => $this->t("This API key can be found on your PayPlug account page."),
      '#default_value' => $this->configuration['live_apikey'],
    ];

    // Input for Test API key.
    $form['test_apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test API key'),
      '#description' => $this->t("This API key can be found on your PayPlug account page."),
      '#default_value' => $this->configuration['test_apikey'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['live_apikey'] = $values['live_apikey'];
      $this->configuration['test_apikey'] = $values['test_apikey'];
    }
  }

  /**
   * {@inheritdoc}
   */
  function onNotify(Request $request) {
    parent::onNotify($request);
    // Initializes the secret API Key regarding of the current mode.
    $this->_initializePayPlugSecretApiKey();
    $input = $request->getContent();
    try {
      $resource = $this->payPlugService->treatPayPlugNotification($input);
      // Handle Payment received.
      if ($resource instanceof \Payplug\Resource\Payment) {
        $payment = $this->_mapPayplugPaymentToCommercePayplug($resource);
        $payment->save();
      }
    } catch (\Payplug\Exception\PayplugException $exception) {
      // Return empty response with 500 error code.
      return new JsonResponse($exception->getMessage(), $exception->getCode());
    }
    // Return empty response with 200 status code.
    return new JsonResponse();
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {

  }

  /**
   * Refunds the given payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to refund.
   * @param \Drupal\commerce_price\Price $amount
   *   The amount to refund. If NULL, defaults to the entire payment amount.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    // TODO: Implement refundPayment() method.
  }

  /**
   * Maps a PayPlug payment object to a Commerce Payment object.
   *
   * @param \Payplug\Resource\Payment $resource
   *   The PayPlug service payment object.
   * @return \Drupal\commerce_payment\Entity\Payment
   *   The Drupal Commerce payment object.
   */
  public function _mapPayplugPaymentToCommercePayplug(\Payplug\Resource\Payment $resource) {
    $metadata = $resource->metadata;
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'authorization',
      'amount' => new Price($resource->amount / 100, $resource->currency),
      'payment_gateway' => $this->entityId,
      'order_id' => $metadata['order_id'],
      'test' => $this->getMode() == 'test',
      'remote_id' => $resource->id,
      'remote_state' => empty($resource->failure) ? 'paid' : $resource->failure->code,
      'authorized' => $this->time->getRequestTime(),
    ]);
    return $payment;
  }

  /**
   * Sets the API key accordingly to the current selected mode.
   */
  public function _initializePayPlugSecretApiKey() {
    if ($this->configuration['mode'] == 'live') {
      $this->payPlugService->setApiKey($this->configuration['live_apikey']);
    } else {
      $this->payPlugService->setApiKey($this->configuration['test_apikey']);
    }
  }
}
