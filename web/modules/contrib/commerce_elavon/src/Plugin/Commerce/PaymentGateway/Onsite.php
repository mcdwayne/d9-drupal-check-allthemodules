<?php

namespace Drupal\commerce_elavon\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Provides the On-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "elavon_onsite",
 *   label = "Elavon (On-site)",
 *   display_label = "Elavon",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_elavon\PluginForm\Onsite\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class Onsite extends OnsitePaymentGatewayBase implements OnsiteInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'multicurrency' => FALSE,
      'merchant_id' => '',
      'user_id' => '',
      'pin' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Id'),
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
    ];

    $form['user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant User Id'),
      '#default_value' => $this->configuration['user_id'],
      '#required' => TRUE,
    ];

    $form['pin'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Pin'),
      '#default_value' => $this->configuration['pin'],
      '#required' => TRUE,
    ];

    $form['multicurrency'] = [
      '#type' => 'radios',
      '#title' => $this->t('Multi-Currency support'),
      '#description' => $this->t('Use only with a terminal that is setup with Multi-Currency.'),
      '#options' => [
        TRUE => $this->t('Support Multi-Currency'),
        FALSE => $this->t('Do Not Support'),
      ],
      '#default_value' => (int) $this->configuration['multicurrency'],
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
      $this->configuration['user_id'] = $values['user_id'];
      $this->configuration['pin'] = $values['pin'];
      $this->configuration['multicurrency'] = $values['multicurrency'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $cardnumber = $payment_method->get('card_number')->value;
    $this->assertPaymentMethod($payment_method);

    $amount = $payment->getAmount()->getNumber();

    if ((int) $amount <= 0) {
      return;
    }

    $payment_method_token = $payment_method->getRemoteId();
    $post_data = [
      'ssl_transaction_type' => $capture ? 'ccsale' : 'ccauthonly',
      'ssl_amount' => $amount,
      'ssl_token' => $payment_method_token,
    ];
    if ($this->configuration['multicurrency']) {
      $post_data['ssl_transaction_currency'] = $payment->getAmount()->getCurrencyCode();
    }
    $response = $this->elavonPost($post_data);

    if ($response['status']) {
      $responseXml = $response['xml'];

      if ($responseXml->ssl_result_message != 'APPROVAL') {
          throw new HardDeclineException('The payment was declined');
          return;
      }
      // The remote ID returned by the request.
      $next_state = $capture ? 'completed' : 'authorization';
      $payment->setState($next_state);
      $payment->setRemoteId($responseXml->ssl_txn_id);
      $payment->save();
    }
    else {
      \Drupal::logger('commerce_elavon')->error(t('Payment could not be processed'));
      throw new DeclineException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();

    // Perform the capture request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    $remote_id = $payment->getRemoteId();
    $number = $amount->getNumber();
    $post_data = [
      'ssl_transaction_type' => 'cccomplete',
      'ssl_amount' => $number,
      'ssl_txn_id' => $remote_id,
    ];
    $response = $this->elavonPost($post_data);

    if ($response['status']) {
      $payment->setState('completed');
      $payment->setAmount($amount);
      $payment->save();
    }
    else {
      \Drupal::logger('commerce_elavon')->error(t('Capture payment could not be processed'));
      throw new DeclineException();
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

    // Converge payment use ccdelete to void authorization.
    $post_data = [
      'ssl_transaction_type' => 'ccdelete',
      'ssl_txn_id' => $remote_id,
    ];
    $response = $this->elavonPost($post_data);

    if ($response['status']) {
      $payment->setState('authorization_voided');
      $payment->save();
    }
    else {
      \Drupal::logger('commerce_elavon')->error(t('Void payment could not be processed'));
      throw new DeclineException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    // Perform the refund request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    $remote_id = $payment->getRemoteId();
    $number = $amount->getNumber();
    $post_data = [
      'ssl_transaction_type' => 'ccreturn',
      'ssl_amount' => $number,
      'ssl_txn_id' => $remote_id,
    ];

    $response = $this->elavonPost($post_data);
    
    if ($response['status']) {
      $responseXml = $response['xml'];

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
    else {
      \Drupal::logger('commerce_elavon')->error(t('Refund payment could not be processed'));
      throw new DeclineException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $required_keys = [
      // The expected keys are payment gateway specific and usually match
      // the PaymentMethodAddForm form elements. They are expected to be valid.
      'type', 'number', 'expiration',
    ];
    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }

    $address = $payment_method->getBillingProfile()->address->first();

    // Perform the create request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    // You might need to do different API requests based on whether the
    // payment method is reusable: $payment_method->isReusable().
    // Non-reusable payment methods usually have an expiration timestamp.
    $payment_method->card_type = $payment_details['type'];
    // Only the last 4 numbers are safe to store.
    $payment_method->card_number = substr($payment_details['number'], -4);
    $payment_method->card_exp_month = $payment_details['expiration']['month'];
    $payment_method->card_exp_year = $payment_details['expiration']['year'];
    $expires = CreditCard::calculateExpirationTimestamp($payment_details['expiration']['month'], $payment_details['expiration']['year']);
    $post_data = [
      'ssl_transaction_type' => 'ccgettoken',
      'ssl_show_form' => 'true',
      'ssl_card_number' => $payment_details['number'],
      'ssl_exp_date' => $payment_details['expiration']['month'] . substr($payment_details['expiration']['year'],2),
      'ssl_cvv2cvc2_indicator' => '1',
      'ssl_verify' => 'Y',
      'ssl_avs_zip' => $address->getPostalCode(),
      'ssl_avs_address' => $address->getAddressLine1() . ' ' . $address->getAddressLine2(),
      'ssl_cvv2cvc2' => $payment_details['security_code'], //$payment_details['security_code'], 
      'ssl_add_token' => 'Y',
      'ssl_first_name' => $address->getGivenName(),
      'ssl_last_name' => $address->getFamilyName(),
    ];

    $response = $this->elavonPost($post_data);

    if (isset($response['xml'])) {
      $result_obj = $response['xml'];
      if (isset($result_obj->ssl_token_response)) {
        if ($result_obj->ssl_token_response == 'SUCCESS') {
          $remote_id = $result_obj->ssl_token;
          // The remote ID returned by the request.
          $payment_method->setRemoteId($remote_id);
          $payment_method->setExpiresTime($expires);
          $payment_method->save();
          return;
        }
        else {
          if (isset($result_obj->errorMessage)) {
            $msg = $result_obj->errorMessage;
          }
          else {
            $msg = t('Payment could not be authorized.');
          }
          \Drupal::logger('commerce_elavon')->error($msg);
        }
      }
      else {
        if (isset($result_obj->errorMessage)) {
          $msg = $result_obj->errorMessage;
        }
        else {
          $msg = t('Structure issue with the returned data from payment gateway.');
        }
        \Drupal::logger('commerce_elavon')->error($msg);
      }
    }
    else {
      \Drupal::logger('commerce_elavon')->error(t('No returned data from payment gateway.'));
    }
    throw new DeclineException();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // No Elavon api avaiabe so far to remove remote record without refunding.
    $payment_method->delete();
  }

  /**
   * Perform a Elavon POST request.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   Payment information.
   * @param array $post_data
   *   Data to be sent to Elavon.
   * @param string $transaction_url
   *   Transaction URL for Elavon.
   *
   * @return array|mixed
   *   Returns XML decoded response from Elavon.
   */
  protected function elavonPost(array $post_data) {
    $response = [];
    $response['status'] = TRUE;

    if ($this->configuration['mode'] === 'test') {
      $transaction_url = 'https://api.demo.convergepay.com/VirtualMerchantDemo/processxml.do';//$this->configuration['transaction_url'];
    }
    else {
      $transaction_url = 'https://api.convergepay.com/VirtualMerchant/processxml.do';
    }

    // Prepare xml for Elavon.
    $auth_data = [
      'ssl_merchant_id' => $this->configuration['merchant_id'],
      'ssl_user_id' => $this->configuration['user_id'],
      'ssl_pin' => $this->configuration['pin'],
    ];
    $xmldata = 'xmldata=<txn>';

    foreach ($auth_data as $key => $value) {
      $xmldata .= '<' . $key . '>' . Html::escape($value) . '</' . $key . '>';
    }

    foreach ($post_data as $key => $value) {
      // Keep keys starting by ssl_.
      if (strpos($key, 'ssl_') !== 0) {
        continue;
      }
      $xmldata .= '<' . $key . '>' . Html::escape($value) . '</' . $key . '>';
    }

    $xmldata .= '</txn>';

    // Revert back the single quote.
    $xmldata = str_replace('&#039;', "'", $xmldata);

    // Setup the cURL request.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $transaction_url);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xmldata);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_NOPROGRESS, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    $result = curl_exec($ch);

    // Log any errors to the watchdog.
    if ($error = curl_error($ch)) {
      \Drupal::logger('commerce_elavon')->error($error);
      $response['status'] = FALSE;
      $response['msg'] = $error;
      return $response;
    }
    curl_close($ch);

    if (!empty($result)) {
      // Extract the result into an XML response object.
      $xml = new \SimpleXMLElement($result);
      $response['msg'] = (string) $xml->ssl_result_message;
      //$response['status'] = ((string) $xml->ssl_result_message === 'APPROVAL') ? TRUE : FALSE;
      // Request approved, Save original xml response containing all the data.
      $response['raw'] = $result;
      $response['xml'] = $xml;
    }
    else {
      \Drupal::logger('commerce_elavon')->error('cURL error empty result returned.');
      $response['status'] = FALSE;
      $response['msg'] = t('No answer from server');
    }
    return $response;
  }
}
