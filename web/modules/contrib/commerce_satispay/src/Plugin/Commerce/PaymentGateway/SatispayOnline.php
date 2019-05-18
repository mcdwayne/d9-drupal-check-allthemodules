<?php

namespace Drupal\commerce_satispay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use SatispayOnline\Api;
use SatispayOnline\Bearer;
use SatispayOnline\Checkout;
use SatispayOnline\Charge;

/**
 * Provides the Satispay payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "satispay_online",
 *   label =  @Translation("Satispay"),
 *   display_label = @Translation("Satispay"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_satispay\PluginForm\SatispayOnlineForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class SatispayOnline extends OffsitePaymentGatewayBase implements SatispayOnlineInterface {

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new PaymentGatewayBase object.
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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   The logger channel factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, LoggerChannelFactoryInterface $logger_channel_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->logger = $logger_channel_factory->get('commerce_satispay');
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
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'security_bearer' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['security_bearer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Security Bearer'),
      '#default_value' => $this->configuration['security_bearer'],
      '#required' => TRUE,
      '#maxlength' => 255,
      '#size' => 150,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    if (!$form_state->getErrors() && $form_state->isSubmitted()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration['security_bearer'] = $values['security_bearer'];
      $this->configuration['mode'] = $values['mode'];

      if (!$this->check()) {
        $form_state->setError($form['security_bearer'], $this->t('Invalid Security Bearer.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['security_bearer'] = $values['security_bearer'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    $uuid = $request->get('uuid');

    // Exit now if the UUID was empty.
    if (empty($uuid)) {
      $this->logger->warning('Callback URL accessed with no UUID submitted.');
      throw new BadRequestHttpException('Callback URL accessed with no UUID submitted.');
    }

    $configuration = $this->getConfiguration();

    Api::setSecurityBearer($configuration['security_bearer']);
    if ($configuration['mode'] == 'test') {
      Api::setStaging(TRUE);
    }
    Api::setClient('Drupal/' . \DRUPAL::VERSION);

    $charge = Charge::get($uuid);

    // TODO: check charge error.
    $this->logger->notice('Charge read: @charge', ['@charge' => json_encode($charge)]);

    // Ensure we can load the existing corresponding transaction.
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    // Temporary workaround with order id.
    $order = \Drupal\commerce_order\Entity\Order::load($charge->metadata->order);
    $payment = array_shift(array_values($payment_storage->loadMultipleByOrder($order)));

    print_r($payment);

    // If not, bail now because authorization transactions should be created
    // by the Express Checkout API request itself.
    if (!$payment) {
      $this->logger->warning('Charge for Order @order_number ignored: authorization transaction already created.', ['@order_number' => $charge->metadata->order]);
      return FALSE;
    }

    // Update the payment state.
    switch ($charge->status) {
      case 'REQUIRED':
        $payment->setState('pending');
        break;

      case 'SUCCESS':
        $payment->setState('completed');
        break;

      case 'FAILURE':
        // TODO.
        /* $payment->setState('completed'); */
        break;
    }

    $payment->setRemoteState($charge->status);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function check() {
    $configuration = $this->getConfiguration();

    Api::setSecurityBearer($configuration['security_bearer']);
    if ($configuration['mode'] == 'test') {
      Api::setStaging(TRUE);
    }
    Api::setClient('Drupal/' . \DRUPAL::VERSION);

    try {
      Bearer::check();
      return TRUE;
    }
    catch (\Exception $ex) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setCheckout(PaymentInterface $payment, array $extra) {
    $order = $payment->getOrder();
    $amount = $payment->getAmount();

    $configuration = $this->getConfiguration();

    Api::setSecurityBearer($configuration['security_bearer']);
    if ($configuration['mode'] == 'test') {
      Api::setStaging(TRUE);
    }
    Api::setClient('Drupal/' . \DRUPAL::VERSION);

    $notify_url = $this->getNotifyUrl();
    $notify_url->setOption('query', ['uuid' => '{uuid}']);

    $callback_url = $notify_url->toString();
    $callback_url = str_replace('uuid=%7Buuid%7D', 'uuid={uuid}', $callback_url);

    // TODO: currency conversion.
    /* $amount->convert($currency_code, $rate = 1) */

    $checkout = Checkout::create([
      'description' => $this->t('Order #@order_number', ['@order_number' => $order->id()]),
      'phone_number' => '',
      'redirect_url' => $extra['return_url'],
      'callback_url' => $callback_url,
      'amount_unit' => (int) $amount->multiply(100)->getNumber(),
      'currency' => $amount->getCurrencyCode(),
      'metadata' => [
        'order' => $order->id(),
      ],
    ]);

    return $checkout;
  }

}
