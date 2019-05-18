<?php

namespace Drupal\commerce_qualpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Link;
use Drupal\Core\Url;


/**
 * Provides the Qualpay payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "qualpay",
 *   label = "Qualpay",
 *   display_label = "Qualpay",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_qualpay\PluginForm\Qualpay\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 *   js_library = "commerce_qualpay/form",
 * )
 */
class Qualpay extends OnsitePaymentGatewayBase implements QualpayInterface {


  /**
   * Convenientstore test API URL for Initial access.
   */
  const TEST_URL = 'https://api-test.qualpay.com';
  const LIVE_URL = 'https://api.qualpay.com';
  
  protected $order;
  protected $orderTransation;
  protected $amount;
  protected $refund_amount;
  protected $payment;


  
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $current_path = \Drupal::service('path.current')->getPath();

    $args = explode("/", $current_path);
    foreach ($args as $key => $value) {
      if (is_numeric($value)) {
        $orderId = $value;
        $this->order = \Drupal\commerce_order\Entity\Order::load($orderId);
        $this->orderTransation = "check" . $this->order->id() . "-" . time();
      }
    }
  }

  
  /**
   * {@inheritdoc}
   */
  public function getSecurity_key() {
    return $this->configuration['security_key'];
  }


  /**
   * {@inheritdoc}
   */
  public function getMerchant_id() {
    return $this->configuration['merchant_id'];
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'security_key' => '',
      'merchant_id' => '',
    ] + parent::defaultConfiguration();
  }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['security_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Security Key'),
      '#default_value' => $this->configuration['security_key'],
      '#required' => TRUE,
    ];
    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Id'),
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    
    $values = $form_state->getValue($form['#parents']);
    $mode = $values['mode'];
    $security_key = $values['security_key'];
    $string = $security_key . ":";
    $base = base64_encode($string);
    
    if($mode == "test") {
      $auth_url = self::TEST_URL . "/platform/embedded";
    }
    else {
      $auth_url = self::LIVE_URL . "/platform/embedded";
    }

    $ch = curl_init($auth_url);
    curl_setopt_array($ch, array(
      CURLOPT_HTTPHEADER  => array('Authorization:Basic ' . $base),
      CURLOPT_RETURNTRANSFER  =>true,
      CURLOPT_VERBOSE     => 1
    ));
    $out = curl_exec($ch);
    $out = json_decode($out);
    
    if($out->code != 0){
      $form_state->setError($form['security_key'], t('You have entered invalid API Key.'));
    }

    if($out->data->merchant_id != $values['merchant_id']) {
      $form_state->setError($form['merchant_id'], t('You have entered invalid Merchant ID.'));
    }
    curl_close($ch);
    // echo response output
  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['security_key'] = $values['security_key'];
      $this->configuration['merchant_id'] = $values['merchant_id'];
    }
  }


  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);

    $amount = $payment->getAmount();
    $transaction_data = [
      'currency' => $amount->getCurrencyCode(),
      'amount' => $this->toMinorUnits($amount),
      'source' => $payment_method->getRemoteId(),
      'capture' => $capture,
    ];

    $owner = $payment_method->getOwner();
    if ($owner && $owner->isAuthenticated()) {
      $transaction_data['customer'] = $this->getRemoteCustomerId($owner);
    }

    $mode = $payment_method->getPaymentGatewayMode();
    $payment_gateway_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment_gateway');
    $payment_gateway = $payment_gateway_storage->load('qualpay');
    
    $payment_details = $_SESSION['payment_details'];
    $amount =  $payment->getAmount()->getNumber();
    $month = (string)$payment_details['expiration']['month'];
    $year = (string)$payment_details['expiration']['year'];
    $year = substr($year, -2);
    $exp_date = $month . $year;
    $security_key = $this->configuration['security_key'];
    $merchant_id = $this->configuration['merchant_id'];
  
    $string = $security_key . ":";
    $base = base64_encode($string);
    $name = $this->order->getStore()->getName();
    
    $reqArray = [
      "merchant_id" => $merchant_id,
      "card_number" => $payment_details['number'],
      "exp_date" => $exp_date,
      "amt_tran" => $amount,
      "purchase_id" => $this->order->id(),
      "cardholder_name" => $payment_details['owner'],
      "developer_id" => 'addwebdrupalver#',
      "moto_ecomm_ind" => 7 
    ];

    $data_string = json_encode($reqArray);
    if($mode == "test") {
      $URL = self::TEST_URL . '/pg/auth';
    } 
    else {
      $URL = self::LIVE_URL . '/pg/auth';
    }

    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json',
        'Authorization:Basic ' . $base
      ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $result_Array = json_decode($result);
    $pg_id = $result_Array->pg_id;
    $amount = $payment->getAmount();
    $_SESSION['payment_details'] = "";
    
    if($result_Array->rcode == "000") {
      $next_state = 'authorization';
      $payment->setState($next_state);
      $payment->setRemoteId($pg_id);  
      $payment->save();
      $this->capturePayment($payment, $amount);
    }
    \Drupal::logger('commerce_qualpay')->notice('aurth: ' . $result);
  }


  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $pg_id = $payment->getRemoteId();
    $mode = $payment->getPaymentGatewayMode();
    $amt_tran = $payment->getAmount()->getNumber();
    $security_key = $this->configuration['security_key'];
    $merchant_id = $this->configuration['merchant_id'];
    $string = $security_key . ":";
    $base = base64_encode($string);

    $reqArray = [
      "merchant_id" => $merchant_id,
      "amt_tran" => $amt_tran
    ];
    
    $data_string = json_encode($reqArray); 
    if($mode == "test") {
      $URL = self::TEST_URL . '/pg/capture/'. $pg_id;      
    }
    else {
      $URL = self::LIVE_URL . '/pg/capture/'. $pg_id;      
    }

    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json',
        'Authorization:Basic ' . $base
      ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $result_Array = json_decode($result);

    if($result_Array->rcode != "000") {
      $this->capturePayment($payment, $amount);
    }
    else {
      $payment->setState('completed');
      $payment->setAmount($amount);
      $payment->save();
      \Drupal::logger('commerce_qualpay')->notice('captchured: ' . $result);
    }
  }


  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);
    // Perform the void request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    $remote_id = $payment->getRemoteId();
    $payment->setState('authorization_voided');
    $payment->save();
  }


  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    $this->order = $payment->getOrder();
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);
    $mode = $payment->getPaymentGatewayMode();
    $pg_id = (string)$payment->getRemoteId();
    $amt_tran = $amount->getNumber();
    $this->refund_amount = $amt_tran; 
    $security_key = $this->configuration['security_key'];
    $merchant_id = $this->configuration['merchant_id'];
    $string = $security_key . ":";
    $base = base64_encode($string);
    
    $reqArray = [
      "amt_tran" => $amt_tran,
      "merchant_id" => $merchant_id
    ];
    $data_string = json_encode($reqArray); 
    
    if($mode == "test") {
      $URL =  self::TEST_URL .'/pg/refund/'. $pg_id;
    }
    else{
      $URL =  self::TEST_URL .'/pg/refund/'. $pg_id;
    }
    
    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json',
        'Authorization:Basic ' . $base
      ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $result_Array = json_decode($result);
    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    
    if($result_Array->rcode == "000") {
      if ($new_refunded_amount->lessThan($payment->getAmount())) {
        $payment->setState('partially_refunded');
      }
      else {
        $payment->setState('refunded');
      }
    }
    else {
      drupal_get_messages('status', TRUE);
      drupal_set_message(t('Refund Failed.'), 'error');
    }
    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
    $this->payment = $payment;
    $this->refund_mail_notification();
    \Drupal::logger('commerce_qualpay')->notice('Refund: ' . $result);
  }


  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $_SESSION['payment_details'] = $payment_details;
    
    // $payment_method->encrypted_card_type = $payment_details['type'];
    // $payment_method->encrypted_card_number = $payment_details['number'];
    // $payment_method->encrypted_card_exp_month = $payment_details['expiration']['month'];
    // $payment_method->encrypted_card_exp_year = $payment_details['expiration']['year'];
    // $payment_method->encrypted_card_cvv = $payment_details['security_code'];
    // Calculate the expiration time.
    $expires = CreditCard::calculateExpirationTimestamp(
      $payment_details['expiration']['month'],
      $payment_details['expiration']['year']
    );
    $payment_method->setExpiresTime($expires);
    // Set the payment method as not reusable.
    // @todo Allow configuring whether the payment methods should be reusable.
    $payment_method->setReusable(FALSE);
    $payment_method->save();
  }


  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
 
  }


  /**
   * Creates the payment method on the gateway.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   * @param array $payment_details
   *   The gateway-specific payment details.
   *
   * @return array
   *   The payment method information returned by the gateway. Notable keys:
   *   - token: The remote ID.
   *   Credit card specific keys:
   *   - card_type: The card type.
   *   - last4: The last 4 digits of the credit card number.
   *   - expiration_month: The expiration month.
   *   - expiration_year: The expiration year.
   */
  protected function doCreatePaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
 
  }


  /**
   * Builds the URL to the payment information checkout step.
   *
   * @return \Drupal\Core\Url
   *   The URL to the payment information checkout step.
   */
  protected function buildPaymentInformationStepUrl() {
    
  }


  /**
   * Redirects to a previous checkout step on error.
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  protected function redirectToPreviousStep() {

  }

  /*
  * Implement to send refund mail notification.
  */
  protected function refund_mail_notification() {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'commerce_qualpay';
    $key = 'refund_payment'; // Replace with Your key
    $to = $this->order->getEmail();
    $message = "Dear Customer,\nWe have processed refund for the following item/s in your order #" . $this->order->id() . ". The amount of $" . $this->refund_amount . " successfully refunded to your account. \n\nThank You.";
     \Drupal::logger('mail-log')->error("msg: " . $message);
    $params['message'] = $message;
    $params['title'] = "Qualpay Refund Payment for Order #". $this->order->id();
    \Drupal::logger('mail-log')->notice("params:" . print_r($params));
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
    \Drupal::logger('mail-log')->notice("result:" .print_r($result));
    if ($result['result'] != true) {
      $message = t('There was a problem sending your email notification to @email.', array('@email' => $to));
      drupal_set_message($message, 'error');
      \Drupal::logger('mail-log')->error($message);
      return;
    }

    $message = t('An email notification has been sent to @email ', array('@email' => $to));
    drupal_set_message($message);
    \Drupal::logger('mail-log')->notice($message);
  }
}
