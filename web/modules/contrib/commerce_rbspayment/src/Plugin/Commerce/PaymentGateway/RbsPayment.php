<?php

namespace Drupal\commerce_rbspayment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_rbspayment\CommerceRbsPaymentApi;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site RBS payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "rbs_payment",
 *   label = "RBS payment",
 *   display_label = "Bank Card",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_rbspayment\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "mir", "jcb", "unionpay", "mastercard", "visa",
 *   },
 * )
 */
class RbsPayment extends OffsitePaymentGatewayBase implements RbsPaymentInterface {

  /**
   * The price rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, RounderInterface $rounder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->rounder = $rounder;
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
      $container->get('commerce_price.rounder')
    );
  }

  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    // TODO: Implement capturePayment() method.
  }

  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);
    $amount = $this->rounder->round($amount);

    $extra['amount'] = $this->toMinorUnits($amount);
    // Check if the Refund is partial or full.
    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->setState('partially_refunded');
      $extra['refund_type'] = 'Partial';
    }
    else {
      $payment->setState('refunded');
      if ($amount->lessThan($payment->getAmount())) {
        $extra['refund_type'] = 'Partial';
      }
      else {
        $extra['refund_type'] = 'Full';
      }
    }

    $response = $this->getApi($payment)->refundOrderPayment($payment->getRemoteId(), $extra['amount']);
    if (isset($result['errorCode']) && $response['errorCode']) {
      drupal_set_message($this->t('Error # %code: %message', [
        '%code' => $response['errorCode'],
        '%message' => $response['errorMessage']
      ]), 'error');
    }

    drupal_set_message($this->t('Payment refund was processed successfully. Payment status will be updated when gateway will notify site.'));
    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    // TODO: Implement voidPayment() method.
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
//      'redirect_method' => 'post',
      'username' => '',
      'password' => '',
      'double_staged' => '',
      'server_url' => '',
      'server_test_url' => '',
      'timeout' => '',
      'logging' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $this->configuration['username'],
    ];
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $this->configuration['password'],
    ];
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key'),
      '#default_value' => $this->configuration['secret_key'],
    ];
// @todo Payment process capture?
//    $form['double_staged'] = [
//      '#type' => 'checkbox',
//      '#title' => $this->t('Double staged'),
//      '#default_value' => $this->configuration['double_staged'],
//    ];

//    define("TEST_URL", 'https://3dsec.sberbank.ru/testpayment/rest/');  //Тестовый шлюз
//    define("PROD_URL", 'https://3dsec.sberbank.ru/payment/rest/');  // Боевой шлюз

    $form['server_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server URL'),
      '#default_value' => $this->configuration['server_url'],
    ];

    $form['server_test_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server Test URL'),
      '#default_value' => $this->configuration['server_test_url'],
    ];

    $form['timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timeout'),
      '#default_value' => $this->configuration['timeout'],
    ];

    $form['logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Logging'),
      '#default_value' => $this->configuration['logging'],
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
      $this->configuration['username'] = $values['username'];
      $this->configuration['secret_key'] = $values['secret_key'];
      if (!empty($values['password'])) {
        $this->configuration['password'] = $values['password'];
      }
      $this->configuration['server_url'] = $values['server_url'];
      $this->configuration['server_test_url'] = $values['server_test_url'];
      $this->configuration['timeout'] = $values['timeout'];
      $this->configuration['logging'] = $values['logging'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    drupal_set_message($this->t('Payment was processed'));
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    $data = $request->query->all();
    if (!empty($this->configuration['secret_key'])) {
      $checksum = $data['checksum'];
      unset($data['checksum']);
      ksort($data);

      $prepared_data = [];
      foreach ($data as $key => $value) {
        $prepared_data[] = $key;
        $prepared_data[] = $value;
      }
      $data_string = implode(';', $prepared_data);
      $data_string .= ';';
      $hmac = hash_hmac('sha256', $data_string, $this->configuration['secret_key']);
      if ($checksum != Unicode::strtoupper($hmac)) {
        return FALSE;
      }
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entityTypeManager->getStorage('commerce_payment')->load($data['orderNumber']);
    if (is_null($payment) || $payment->getRemoteId() != $data['mdOrder']) {
      return FALSE;
    }

    $status_response = $this->getApi($payment)->getOrderStatusByRBSOrderId($data['mdOrder']);
    switch ($data['operation']) {
      case 'approved':
        break;
      case 'deposited':
        $status = $this->doDeposited($payment, $status_response);
        break;
      case 'reversed':
      case 'refunded':
        $status = $this->doRefunded($payment, $status_response);
        break;
      case 'declinedByTimeout':
        $status = $this->doCancel($payment, $status_response);
        break;
    }

    if (!$status) {
      return FALSE;
    }

    $payment->save();
    $payment->getOrder()->setData($this->getPluginId(), $status_response)->save();
  }

  protected function doCancel(PaymentInterface $payment, array $status_response) {
    $payment->setRemoteState($status_response['paymentAmountInfo']['paymentState']);
    $payment->setState('authorization_expired');

    $order = $payment->getOrder();
    $order->unlock();
    $transition = $order->getState()->getWorkflow()->getTransition('cancel');
    $order->getState()->applyTransition($transition);
    $order->save();

    return TRUE;
  }

  protected function doRefunded(PaymentInterface $payment, array $status_response) {
    //@todo need additional checks here ?
    $response_amount = intdiv($status_response['paymentAmountInfo']['refundedAmount'], 100);
    $refunded_amount = new Price($response_amount, $this->getCurrencyCode($status_response['currency']));
    $payment->setRefundedAmount($refunded_amount);
    $payment->setRemoteState($status_response['paymentAmountInfo']['paymentState']);
    $this->setLocalState($payment, $status_response['orderStatus']);

    return TRUE;
  }

  protected function doDeposited(PaymentInterface $payment, array $status_response) {
    $response_amount = intdiv($status_response['amount'], 100);
    $gateway_price = new Price($response_amount, $this->getCurrencyCode($status_response['currency']));
    if (!$payment->getAmount()->equals($gateway_price)) {
      return FALSE;
    }
    $payment->setRemoteState($status_response['paymentAmountInfo']['paymentState']);
    $this->setLocalState($payment, $status_response['orderStatus']);

    $order = $payment->getOrder();
    $order->unlock();
    $transition = $order->getState()->getWorkflow()->getTransition('place');
    $order->getState()->applyTransition($transition);
    $order->save();

    return TRUE;
  }

  /**
   * @param string $currency_code
   *
   * @return string
   */
  function getCurrencyCode($numeric_currency_code) {
    /** @var Currency[] $currencies */
    $currencies = Currency::loadMultiple();
    foreach ($currencies as $currency) {
      if ($currency->getNumericCode() == $numeric_currency_code) {
        return $currency->getCurrencyCode();
      }
    }
    return NULL;
  }

  public function getApi(PaymentInterface $payment) {
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $payment_gateway_configuration = $payment_gateway_plugin->getConfiguration();
    $user_name = $payment_gateway_configuration['username'];
    $password = $payment_gateway_configuration['password'];
    // @todo dinamic capture.
//    $double_staged = !$form['#capture'];
    $double_staged = FALSE;
    $mode = $payment->getPaymentGatewayMode() == 'live' ? false : true;
    $logging = $payment_gateway_configuration['logging'] == 0 ? false : true;
    $timeout = $payment_gateway_configuration['timeout'];
    $url = $payment_gateway_configuration['server_url'];
    $test_url = $payment_gateway_configuration['server_test_url'];

    return new CommerceRbsPaymentApi($url, $test_url, $user_name, $password, $timeout, $double_staged, $mode, $logging);
  }

  /**
 * Sets transaction 'status' and 'message' depending on RBS status.
 *
 * @param object $transaction
 * @param int $remote_status
 */
public function setLocalState(PaymentInterface $payment, $remote_status) {
  switch ($remote_status) {
    case CommerceRBSPaymentAPI::orderStatusPending:
      break;
    case CommerceRBSPaymentAPI::orderStatusPreHold:
      $payment->setState('authorization');
      break;

    case CommerceRBSPaymentAPI::orderStatusAuthorized:
      $payment->setState('completed');
      break;

    case CommerceRBSPaymentAPI::orderStatusDeclined:
      $payment->setState('authorization_voided');
      break;

    case CommerceRBSPaymentAPI::orderStatusPartlyRefunded:
      $status = $payment->getBalance()->isZero() ? 'refunded' : 'partially_refunded';
      $payment->setState($status);
      break;

    case CommerceRBSPaymentAPI::orderStatusReversed:
      $payment->setState('refunded');
      break;
  }

}

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    parent::onCancel($order, $request);
  }


  /**
   * {@inheritdoc}
   */
  public function buildPaymentOperations(PaymentInterface $payment) {
    $payment_state = $payment->getState()->value;
    $operations = parent::buildPaymentOperations($payment);
    // @todo Grant reversal access to single staged payment.

    return $operations;
  }
}
