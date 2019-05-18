<?php

namespace Drupal\commerce_vipps\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_vipps\VippsManager;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use zaporylie\Vipps\Exceptions\VippsException;
use zaporylie\Vipps\Model\OrderStatus;
use Drupal\commerce_price\Price;

/**
 * Provides the Vipps payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "vipps",
 *   label = "Vipps Checkout",
 *   display_label = "Vipps",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_vipps\PluginForm\OffsiteRedirect\VippsLandingPageRedirectForm",
 *   },
 * )
 */
class Vipps extends OffsitePaymentGatewayBase implements SupportsAuthorizationsInterface, SupportsRefundsInterface, SupportsNotificationsInterface {

  /**
   * Vipps manager.
   *
   * @var \Drupal\commerce_vipps\VippsManager
   */
  protected $vippsManager;

  /**
   * Vipps constructor.
   *
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, VippsManager $vippsManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->vippsManager = $vippsManager;
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
      $container->get('commerce_vipps.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'client_id' => '',
      'subscription_key_authorization' => '',
      'client_secret' => '',
      'subscription_key_payment' => '',
      'serial_number' => '',
      'prefix' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => t('Client ID'),
      '#required' => TRUE,
      '#description' => t('Client ID'),
      '#default_value' => $this->configuration['client_id'],
    ];
    $form['subscription_key_authorization'] = [
      '#type' => 'textfield',
      '#title' => t('Subscription Key - Authorization'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['subscription_key_authorization'],
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => t('Client secret'),
      '#required' => TRUE,
      '#description' => t('Client Secret'),
      '#default_value' => $this->configuration['client_secret'],
    ];
    $form['subscription_key_payment'] = [
      '#type' => 'textfield',
      '#title' => t('Subscription Key - Payment'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['subscription_key_payment'],
    ];
    $form['serial_number'] = [
      '#type' => 'textfield',
      '#title' => t('Serial Number'),
      '#required' => TRUE,
      '#description' => t('Merchant Serial Number'),
      '#default_value' => $this->configuration['serial_number'],
    ];
    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => t('Prefix'),
      '#description' => t('Add alphanumeric prefix to Order ID in Vipps, in case you\'re creating Vipps payments from multiple independent systems'),
      '#default_value' => $this->configuration['prefix'],
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
      $this->configuration['client_id'] = $values['client_id'];
      $this->configuration['subscription_key_authorization'] = $values['subscription_key_authorization'];
      $this->configuration['client_secret'] = $values['client_secret'];
      $this->configuration['subscription_key_payment'] = $values['subscription_key_payment'];
      $this->configuration['serial_number'] = $values['serial_number'];
      $this->configuration['prefix'] = $values['prefix'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $remote_id = $order->getData('vipps_current_transaction');
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $matching_payments = $payment_storage->loadByProperties(['remote_id' => $remote_id, 'order_id' => $order->id()]);
    if (count($matching_payments) !== 1) {
      // @todo: Log exception.
      return new Response('', Response::HTTP_FORBIDDEN);
    }
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $matching_payment */
    $matching_payment = reset($matching_payments);
    $payment_manager = $this->vippsManager->getPaymentManager($matching_payment->getPaymentGateway()->getPlugin());
    $status = $payment_manager->getOrderStatus($remote_id);

    switch ($status->getTransactionInfo()->getStatus()) {
      case 'RESERVE':
        $matching_payment->setState('authorization');
        $matching_payment->save();
        break;
      case 'SALE':
        $matching_payment->setState('completed');
        $matching_payment->save();
        break;
      case 'RESERVE_FAILED':
      case 'SALE_FAILED':
      case 'CANCEL':
      case 'REJECTED':
        // @todo: There is no corresponding state in payment workflow but it's
        // still better to keep the payment with invalid state than delete it
        // entirely.
        $matching_payment->setState('failed');
        $matching_payment->setRemoteState(Xss::filter($status->getTransactionInfo()->getStatus()));
        $matching_payment->save();

      default:
        throw new PaymentGatewayException("Oooops, something went wrong.");
    }
    // Seems like payment went through. Enjoy!
  }

  /**
   * Vipps treats onReturn and onCancel in the same way.
   *
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    parent::onReturn($order, $request);
  }

  /**
   * {@inheritdoc}
   *
   * Checks for status changes, and saves it.
   */
  public function onNotify(Request $request) {

    // @todo: Validate order and payment existance.

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $commerce_payment_gateway */
    $commerce_payment_gateway = $request->attributes->get('commerce_payment_gateway');

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $request->attributes->get('order');
    if (!$order instanceof OrderInterface) {
      return new Response('', Response::HTTP_FORBIDDEN);
    }

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    // Validate authorization header.
    if ($order->getData('vipps_auth_key') !== $request->headers->get('Authorization')) {
      return new Response('', Response::HTTP_FORBIDDEN);
    }

    $content = $request->getContent();

    $remote_id = $request->attributes->get('remote_id');
    $matching_payments = $payment_storage->loadByProperties(['remote_id' => $remote_id, 'payment_gateway' => $commerce_payment_gateway->id()]);
    if (count($matching_payments) !== 1) {
      // @todo: Log exception.
      return new Response('', Response::HTTP_FORBIDDEN);
    }
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $matching_payment */
    $matching_payment = reset($matching_payments);
    $old_state = $matching_payment->getState()->getId();

    $content = json_decode($content, TRUE);
    switch ($content['transactionInfo']['status']) {
      case 'RESERVED':
        $matching_payment->setState('authorization');
        break;
      case 'SALE':
        $matching_payment->setState('completed');
        break;
      case 'RESERVE_FAILED':
      case 'SALE_FAILED':
      case 'CANCELLED':
      case 'REJECTED':
        // @todo: There is no corresponding state in payment workflow but it's
        // still better to keep the payment with invalid state than delete it
        // entirely.
        $matching_payment->setState('failed');
        $matching_payment->setRemoteState(Xss::filter($content['transactionInfo']['status']));
        break;

      default:
        \Drupal::logger('commerce_vipps')->critical('Data: @data', ['@data' => $content]);
        return new Response('', Response::HTTP_I_AM_A_TEAPOT);
    }
    $matching_payment->save();

    //// OrderEvent::ORDER_PAID only dispatches when
    //if ($old_state === 'new'
    //  && $matching_payment->getState()->getId() === 'authorization'
    //  && $order->getTotalPrice()->subtract($matching_payment->getAmount())->lessThanOrEqual(new Price(0, $order->getTotalPrice()->getCurrencyCode()))) {
    //
    //  // The order has already been placed.
    //  if ($order->getState()->getId() != 'draft') {
    //    return new Response('', Response::HTTP_OK);
    //  }
    //
    //  $order->getState()->applyTransitionById('place');
    //  // A placed order should never be locked.
    //  $order->unlock();
    //  $order->save();
    //}

    return new Response('', Response::HTTP_OK);
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    // Assert things.
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();

    $remote_id = $payment->getRemoteId();
    $number = $amount->multiply(100)->getNumber();
    try {
      $payment_manager = $this->vippsManager->getPaymentManager($payment->getPaymentGateway()->getPlugin());
      // @todo: Pass formatted number.
      $payment_manager->capturePayment($remote_id,
        $this->t('Captured @amount via webshop',
          ['@amount' => $amount->getNumber()]), $number);
    }
    catch (VippsException $exception) {
      if ($exception->getError()->getCode() == 61) {
        // Insufficient funds.
        // Check if order has already been captured and for what amount,
        foreach ($payment_manager->getPaymentDetails($remote_id)->getTransactionLogHistory() as $item) {
          if (in_array($item->getOperation(), ['CAPTURE', 'SALE']) && $item->getOperationSuccess()) {
            $payment->setAmount(new Price($item->getAmount() / 100, $payment->getAmount()->getCurrencyCode()));
            $payment->setCompletedTime($item->getTimeStamp()->getTimestamp());
            $payment->setState('completed');
            $payment->save();
            // @todo: Sum up all capture transactions - Vipps allow partial
            // capture.
            return;
          }
        }
      }
      throw new DeclineException($exception->getMessage());
    }
    catch (\Exception $exception) {
      throw new DeclineException($exception->getMessage());
    }

    $payment->setState('completed');
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);
    $remote_id = $payment->getRemoteId();
    try {
      $payment_manager = $this->vippsManager->getPaymentManager($payment->getPaymentGateway()->getPlugin());
      $payment_manager->cancelPayment($remote_id, $this->t('Canceled via webshop'));
    }
    catch (\Exception $exception) {
      throw new DeclineException($exception->getMessage());
    }

    $payment->setState('authorization_voided');
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    // Validate.
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);

    // Let's do some refunds.
    parent::assertRefundAmount($payment, $amount);

    $remote_id = $payment->getRemoteId();
    $number = $amount->multiply(100)->getNumber();
    try {
      $payment_manager = $this->vippsManager->getPaymentManager($payment->getPaymentGateway()->getPlugin());
      // @todo: Pass formatted number.
      $payment_manager->refundPayment($remote_id,
        $this->t('Refunded @amount via webshop',
          ['@amount' => $amount->getNumber()]), $number);
    }
    catch (\Exception $exception) {
      throw new DeclineException($exception->getMessage());
    }

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->setState('partially_refunded');
    }
    else {
      $payment->setState('refunded');
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();

  }

}
