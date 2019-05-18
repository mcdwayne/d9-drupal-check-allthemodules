<?php

namespace Drupal\commerce_clic\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentGatewayInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "clic",
 *   label = @Translation("Clic"),
 *   display_label = @Translation("Clic"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_clic\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 *   modes= {
 *     "test" = "Test",
 *     "live" = "Live"
 *   },
 *   payment_method_types = {"crypto_currency"},
 * )
 */
class Clic extends OffsitePaymentGatewayBase implements ClicInterface {

  use LoggerChannelTrait;
  use MessengerTrait;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new Clic PaymentGatewayBase object.
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
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
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
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSecretKey() {
    return $this->configuration['secret_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicKey() {
    return $this->configuration['public_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'public_key' => '',
      'secret_key' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public key'),
      '#description' => $this->t('Enter your Clic Public Key.'),
      '#default_value' => $this->getPublicKey(),
      '#required' => TRUE,
    ];

    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key'),
      '#description' => $this->t('Enter your Clic Secret Key.'),
      '#default_value' => $this->getSecretKey(),
      '#required' => TRUE,
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
      $this->configuration['public_key'] = $values['public_key'];
      $this->configuration['secret_key'] = $values['secret_key'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    if (!$data = $request->request->get('data')) {
      throw new PaymentGatewayException('The return request data is missing.');
    }

    $data = Json::decode($data);
    $check_message = empty($data['message']) || $data['message'] != 'success';
    $empty_amount = empty($data['commerce_clic_data']['transaction']['amount']);
    $empty_currency = empty($data['commerce_clic_data']['transaction']['currency']);

    if ($check_message || $empty_amount || $empty_currency) {
      throw new PaymentGatewayException('The return request data is incorrect.');
    }

    $commerce_clic_data = $data['commerce_clic_data'];
    $payment_amount = new Price((string) $commerce_clic_data['transaction']['amount'], $commerce_clic_data['transaction']['currency']);
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this
      ->entityTypeManager
      ->getStorage('commerce_payment_gateway')
      ->load($this->entityId);

    // Sometimes the payment can be processed shortly and Clic will do IPN
    // request firstly and then the widget will call "success" callback.
    // To avoid payments duplication try to search for a payment.
    $payment = $this->getOrderClicPayment($order, $payment_amount, $payment_gateway, 'completed');
    if (!$payment) {
      $payment = $this
        ->entityTypeManager
        ->getStorage('commerce_payment')
        ->create([
          'state' => 'authorization',
          'amount' => $payment_amount,
          'payment_gateway' => $this->entityId,
          'order_id' => $order->id(),
          'test' => $this->getMode() == 'test',
          'authorized' => $this->time->getRequestTime(),
        ]);
      $payment->save();
    }

    if (!empty($data['cardToken']) && $payment) {
      $clic_card_tokens = $order->getData('clic_card_tokens', []);
      $clic_card_tokens[$payment->id()] = $data['cardToken'];
      $order->setData('clic_card_tokens', $clic_card_tokens);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    if ($data = $request->request->get('data')) {
      $data = Json::decode($data);

      if (!empty($data['message']) && $data['message'] == 'failed') {
        $this->messenger()->addError('An error occurred while attempting to process a payment. Please try again later.');

        if (!empty($data['error'])) {
          $this
            ->getLogger('commerce_clic')
            ->error('An error occurred while attempting to process a payment. Request data: @data', [
              '@data' => print_r($data, TRUE),
            ]);
        }

        return NULL;
      }
    }

    parent::onCancel($order, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    if (!$request->headers->has(static::CLIC_AUTH_HEADER)
      || $this->getSecretKey() != $request->headers->get(static::CLIC_AUTH_HEADER)) {
      throw new AccessDeniedHttpException();
    }

    $request_content = $request->getContent();
    $request_content = Json::decode($request_content);

    if (empty($request_content['orderId'])
      || empty($request_content['status'])
      || empty($request_content['amount'])
      || empty($request_content['customData']['currency'])) {
      $this
        ->getLogger('commerce_clic')
        ->error('The return request data is missing required parameters. Data: @data', [
          '@data' => print_r($request_content, TRUE),
        ]);
      return new JsonResponse(['status' => FALSE]);
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->routeMatch->getParameter('commerce_payment_gateway');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this
      ->entityTypeManager
      ->getStorage('commerce_order')
      ->load($request_content['orderId']);
    $payment_amount = new Price((string) $request_content['amount'], $request_content['customData']['currency']);

    if ($payment = $this->getOrderClicPayment($order, $payment_amount, $payment_gateway, 'authorization')) {
      $transition = $request_content['status'] == 'success' ? 'capture' : 'void';
      $transition = $payment->getState()->getWorkflow()->getTransition($transition);
      $payment->getState()->applyTransition($transition);
    }
    elseif ($request_content['status'] == 'success') {
      // If for some reason there is no payment - create a new one.
      $payment = $this
        ->entityTypeManager
        ->getStorage('commerce_payment')
        ->create([
          'state' => 'completed',
          'amount' => $payment_amount,
          'payment_gateway' => $payment_gateway->id(),
          'order_id' => $order->id(),
          'test' => $this->getMode() == 'test',
          'authorized' => $this->time->getRequestTime(),
        ]);
    }

    if ($payment) {
      if (!empty($request_content['transactionId'])) {
        $payment->setRemoteId($request_content['transactionId']);
      }
      if ($request_content['status'] == 'success') {
        $payment->setCompletedTime($this->time->getRequestTime());
      }

      $payment->save();
    }

    return new JsonResponse(['status' => TRUE]);
  }

  /**
   * Loads a payment in the authorization state for the provided order.
   *
   * Searches by the payment state, payment amount and payment gateway.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_price\Price $payment_amount
   *   The payment amount to search by.
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway
   *   The payment gateway to search by.
   * @param string $payment_status
   *   The payment status to search by.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface|null
   *   The payment if found or NULL otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getOrderClicPayment(OrderInterface $order, Price $payment_amount, PaymentGatewayInterface $payment_gateway, $payment_status) {
    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    foreach ($payment_storage->loadMultipleByOrder($order) as $payment) {
      $state = $payment->getState()->value == $payment_status;
      $amount_equals = $payment->getAmount()->equals($payment_amount);
      $payment_gateway_check = $payment->getPaymentGatewayId() == $payment_gateway->id();

      if ($state && $amount_equals && $payment_gateway_check) {
        $clic_payment = $payment;
        break;
      }
    }

    if (!empty($clic_payment)) {
      return $clic_payment;
    }
  }

}
