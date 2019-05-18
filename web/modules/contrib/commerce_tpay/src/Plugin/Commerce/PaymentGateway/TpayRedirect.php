<?php

namespace Drupal\commerce_tpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_tpay\Event\TpayPaymentEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Url;
use Drupal\commerce_tpay\Controller\TransactionNotification;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "tpay_redirect",
 *   label = "Tpay Redirect",
 *   display_label = "Tpay Redirect",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_tpay\PluginForm\TpayRedirect\TpayPaymentForm",
 *   },
 * )
 */
class TpayRedirect extends OffsitePaymentGatewayBase {
  
  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;
  
  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;
  
  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;
  
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, LoggerChannelFactoryInterface $logger_channel_factory, EventDispatcherInterface $event_dispatcher, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->logger = $logger_channel_factory->get('commerce_tpay');
    $this->eventDispatcher = $event_dispatcher;
    $this->time = $time;
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
      $container->get('logger.factory'),
      $container->get('event_dispatcher'),
      $container->get('datetime.time')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    
    $merchant_id = $this->configuration['merchant_id'];
    $merchant_secret = $this->configuration['merchant_secret'];
    
    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Identification'),
      '#default_value' => $merchant_id,
      '#description' => $this->t('Merchant identification number given during registration'),
      '#required' => TRUE,
    ];
    
    $form['merchant_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Secret'),
      '#default_value' => $merchant_secret,
      '#description' => $this->t('Merchant security code is set in merchant configuration panel in Settings > Notifications, section Security'),
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
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['merchant_secret'] = $values['merchant_secret'];
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    drupal_set_message($this->t('Your order with number: @orderid finished succesfully', ['@orderid' => $order->id()]));
  }
  
  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    drupal_set_message($this->t('Payment @gateway was unsuccessful, you may resume payment processs anytime.', [
      '@gateway' => $this->getDisplayLabel(),
    ]), 'error');
  }
  
  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    $merchant_id = $this->configuration['merchant_id'];
    $merchant_secret = $this->configuration['merchant_secret'];
    
    $transaction = (new TransactionNotification((int)$merchant_id, $merchant_secret))->checkPayment();
    
    if ($transaction) {
      $tr_id = $transaction['tr_id'];
      $tr_crc = $transaction['tr_crc'];
      $tr_paid = $transaction['tr_paid'];
      $tr_status = $transaction['tr_status'];
      $tr_error = $transaction['tr_error'];
      
      $crc_pieces = explode("/", $tr_crc);
      $order_id = $crc_pieces[0];
      $currency = $crc_pieces[1];
      
      if ($tr_status == 'TRUE') {
        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        $order = Order::load($order_id);
        $existing_payments = $payment_storage->loadMultipleByOrder($order);
        
        $payment_logged = FALSE;
        foreach ($existing_payments as $payment) {
          if ($payment->getRemoteId() == $tr_id) {
            $payment_logged = TRUE;
            break;
          }
        }
        
        if (!$payment_logged) {
          $payment = $payment_storage->create([
            'state' => 'completed',
            'amount' => new Price((string) $tr_paid, $currency),
            'payment_gateway' => $this->entityId,
            'order_id' => $order_id,
            'remote_id' => $tr_id,
            'remote_state' => $tr_error,
            'completed' => $this->time->getRequestTime(),
          ]);
          
          $payment->save();
          
          $event = new TpayPaymentEvent($payment);
          
          $this->eventDispatcher->dispatch(TpayPaymentEvent::TPAY_PAYMENT_RECEIVED, $event);
        }
      }
    } else {
      $this->logger->alert($this->t('Wrong checksum'));
    }
    
    $response = new Response('TRUE', Response::HTTP_OK);
    return $response;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getNotifyUrl() {
    return Url::fromRoute('commerce_payment.notify', [
      'commerce_payment_gateway' => $this->entityId,
    ], ['absolute' => TRUE])->toString();
  }
  
  public function getReturnUrl(OrderInterface $order) {
    return Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
  }
  
  public function getCancelUrl(OrderInterface $order) {
    return Url::fromRoute('commerce_payment.checkout.cancel', [
      'commerce_order' => $order->id(),
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
  }
  
}
