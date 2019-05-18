<?php
namespace Drupal\commerce_forte\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\commerce_price\RounderInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Psr\Log\LoggerInterface;



/**
 * Provides the Forte.net  payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "forte_net",
 *   label = "Forte(Credit Card)",
 *   display_label = "Forte Credit Card",
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "discover", "mastercard", "visa",
 *   },
 * )
 */
class Forte extends OnsitePaymentGatewayBase implements ForteInterface {

  /**
   * Forte test API URL.
   */
  const COMMERCE_FORTE_TXN_MODE_LIVE = 'https://api.forte.net/v2/';

  /**
   * Forte production API URL.
   */
  const COMMERCE_FORTE_TXN_MODE_DEVELOPER = 'https://sandbox.forte.net/api/v2/';

  const COMMERCE_CREDIT_AUTH_CAPTURE = 'sale';
  const COMMERCE_CREDIT_AUTH_ONLY = 'authorize';



  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The rounder.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, ClientInterface $client, RounderInterface $rounder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->httpClient = $client;
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
      $container->get('http_client'),
      $container->get('commerce_price.rounder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'login' => '',
      'tran_key' => '',
      'mercacct_id' => '',
      'restacct_id' => '',
      'txn_type' => self::COMMERCE_CREDIT_AUTH_CAPTURE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['login'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rest API Access ID'),
      '#description' => $this->t('Your REST API ACCESS ID is different from the username you use to login to your REST Forte.Net account. Once you login, browse to your API tab and click the <em>Rest API Access ID and Rest API Secure Key</em> link to find your REST API Access ID. If you are using a new REST forte.Net account, you may still need to generate an ID.'),
      '#default_value' => $this->configuration['login'],
      '#required' => TRUE,
    ];
    $form['tran_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rest API Secure Key'),
      '#description' => $this->t('Your Rest API Secure Key can be found on the same screen as your Rest API Access ID. However, it will not be readily displayed. You must generate the secure key a form to see your REST API Secure Key.'),
      '#default_value' => $this->configuration['tran_key'],
      '#required' => TRUE,
    ];
    $form['mercacct_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Account ID'),
      '#description' => $this->t('Your Merchant Account ID is different from the username you use to login to your https://forte.net/  account. Once you login, browse to your Account tab and copy the Account ID'),
      '#default_value' => $this->configuration['mercacct_id'],
      '#required' => TRUE,
    ];
    $form['restacct_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rest Account ID'),
      '#description' => $this->t('Only needed if you wish to change the stored value.'),
      '#description' => $this->t('Adjust to live transactions when you are ready to start processing real payments.') . '<br />' . $this->t('Only specify a developer test account if you login to your account through https://sandbox.paymentsgateway.net.'),
      '#default_value' => $this->configuration['restacct_id'],
      '#required' => TRUE,
    ];
    $form['txn_type'] = [
    '#type' => 'radios',
    '#title' => $this->t('Default credit card transaction type'),
    '#description' => $this->t('The default will be used to process transactions during checkout.'),
    '#options' => [
      self::COMMERCE_CREDIT_AUTH_CAPTURE => $this->t('Authorization and capture'),
      self::COMMERCE_CREDIT_AUTH_ONLY => $this->t('Authorization only (requires manual or automated capture after checkout)'),
    ],
    '#default_value' => $this->configuration['txn_type'],
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

      $this->configuration['login'] = $values['login'];
      $this->configuration['tran_key'] = $values['tran_key'];
      $this->configuration['mercacct_id'] = $values['mercacct_id'];
      $this->configuration['restacct_id'] = $values['restacct_id'];
      $this->configuration['mode'] = $values['mode'];
      $this->configuration['txn_type'] = $values['txn_type'];
     
    }

  }


  /**
   * Returns the Api URL.
   */
  public function getApiUrl() {
        
    // Get the API endpoint URL for the method's transaction mode.
    $merchant_ids = 'loc_' . $this->configuration['mercacct_id'];
    $restacct_id = 'act_' . $this->configuration['restacct_id'];
    $act_loc_url = 'accounts/' . $restacct_id . '/locations/' . $merchant_ids . '/transactions/';
            
    return $this->getMode() == 'test' ? self::COMMERCE_FORTE_TXN_MODE_DEVELOPER.$act_loc_url : self::COMMERCE_FORTE_TXN_MODE_LIVE.$act_loc_url;
    
  }

  /**
   * Returns the partner.
   */
  protected function getLogin() {
    return $this->configuration['login'] ?: '';
  }

  /**
   * Returns the vendor.
   */
  protected function getTransKey() {
    return $this->configuration['tran_key'] ?: '';
  }

  /**
   * Returns the user.
   */
  protected function getMercAcctid() {
    return $this->configuration['mercacct_id'] ?: '';
  }

  /**
   * Returns the password.
   */
  protected function getRestAcctid() {
    return $this->configuration['restacct_id'] ?: '';
  }

  protected function getTxnType() {
    return $this->configuration['txn_type'] ?: '';
  }
  /**
   * Format the expiration date for Forte from the provided payment details.
   *
   * @param array $payment_details
   *   The payment details array.
   *
   * @return string
   *   The expiration date string.
   */
  protected function getExpirationDate(array $payment_details) {
    return $payment_details['expiration']['month'] . $payment_details['expiration']['year'];
  }

  /**
   * Merge default Forte parameters in with the provided ones.
   *
   * @param array $parameters
   *   The parameters for the transaction.
   *
   * @return array
   *   The new parameters.
   */
  protected function getParameters(array $parameters = []) {

    $defaultParameters = [
      //'tender' => 'C',
      'login' => $this->getLogin(),
      'tran_key' => $this->getTransKey(),
      'mercacct_id' => $this->getMercAcctid(),
      'restacct_id' => $this->getRestAcctid(),
      'txn_mode' => $this->getMode(),
      'action'=> $this->getTxnType(),
    ];
  
    return $parameters + $defaultParameters;
  }

  /**
   * Prepares the request body to name/value pairs.
   *
   * @param array $parameters
   *   The request parameters.
   *
   * @return string
   *   The request body.
   */
  protected function prepareBody(array $parameters = []) {
    $parameters = $this->getParameters($parameters);
    

    $values = [];
    foreach ($parameters as $key => $value) {
      $values[] = strtoupper($key) . '=' . $value;
    }

    return implode('&', $values);
  }

  /**
   * Prepares the result of a request.
   *
   * @param string $body
   *   The result.
   *
   * @return array
   *   An array of the result values.
   */
  protected function prepareResult($body) {
    $responseParts = explode('&', $body);

    $result = [];
    foreach ($responseParts as $bodyPart) {
      list($key, $value) = explode('=', $bodyPart, 2);
      $result[strtolower($key)] = $value;
    }

    return $result;
  }

  /**
   * Post a transaction to the Forte server and return the response.
   *
   * @param array $parameters
   *   The parameters to send (will have base parameters added).
   *
   * @return array
   *   The response body data in array format.
   */
  protected function executeTransaction(array $parameters) {
       
    $params = $this->getParameters();
    $APILoginID = $params['login'];
    $SecureTransactionKey = $params['tran_key'];
    $data = $parameters;
    $apiUrl = $this->getApiUrl();
    $transaction_type = $this->getTxnType();
    $capture_only = FALSE;
    if ($transaction_type == 'authorize') {
      $apiUrl = $apiUrl . $data['transaction_id'];
      $capture_only = TRUE;
    }

    $rest_hash = $APILoginID.':'.$SecureTransactionKey;
    $auth_token = base64_encode($rest_hash);
   
  try {
    $data_array = [    
      'headers' => [
        'Authorization' => 'Basic ' . $auth_token,
        'x-forte-auth-account-id' => 'act_' . $this->getRestAcctid(),
      ],
      'json' => $data
    ];
    if ($capture_only && ($data['action'] == 'Capture' || $data['action'] == 'void'))
      $response = $this->httpClient->put($apiUrl, $data_array);
    else
      $response = $this->httpClient->post($apiUrl, $data_array);  
  }
  catch (RequestException $e) {
    throw new PaymentGatewayException('Count not make the payment. Message: ' . $e->getMessage(), $e->getCode(), $e);
  }
  return json_decode($response->getBody()->getContents());
  }

  /**
   * Attempt to validate payment information according to a payment state.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to validate.
   * @param string|null $payment_state
   *   The payment state to validate the payment for.
   */
  protected function validatePayment(PaymentInterface $payment, $payment_state = 'new') {
    $this->assertPaymentState($payment, [$payment_state]);

    $payment_method = $payment->getPaymentMethod();
    if (empty($payment_method)) {
      throw new InvalidArgumentException('The provided payment has no payment method referenced.');
    }

    switch ($payment_state) {
      case 'new':
        if ($payment_method->isExpired()) {
          throw new HardDeclineException('The provided payment method has expired.');
        }

        break;

      case 'authorization':
        if ($payment->isExpired()) {
          throw new \InvalidArgumentException('Authorizations are guaranteed for up to 29 days.');
        }
        if (empty($payment->getRemoteId())) {
          throw new \InvalidArgumentException('Could not retrieve the transaction ID.');
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->validatePayment($payment, 'new');
    $payment_method = $payment->getPaymentMethod(); 
    $this->assertPaymentMethod($payment_method);

    // Add a built in test for testing decline exceptions.
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billing_address */
    if ($billing_address = $payment_method->getBillingProfile()) {
      $billing_address = $payment_method->getBillingProfile()->get('address')->first();
    }
    else
    {
      throw new HardDeclineException('Address not Getting: ');
    }

    $params = $this->getParameters();
    $APILoginID = $params['login'];
    $SecureTransactionKey = $params['tran_key'];
    $rest_hash = $APILoginID.':'.$SecureTransactionKey;
    $auth_token = base64_encode($rest_hash);
    $capture_full_name = $billing_address->getGivenName().' '.$billing_address->getFamilyName();
    $credit_card_decode = base64_decode($payment->getPaymentMethod()->getRemoteId());
    $card_details = explode(',', $credit_card_decode);
    try {
      $data = $this->executeTransaction([
		'action' => $this->getTxnType(),
        'order_number' => $payment->getOrderId(),
        'authorization_amount' => $this->rounder->round($payment->getAmount())->getNumber(),
        'billing_address' => array(
          'first_name' => $billing_address->getGivenName(),
          'last_name' => $billing_address->getFamilyName(),
          'physical_address' => array(
            'street_line1' => $billing_address->getAddressLine1(),
            'locality' =>  $billing_address->getLocality(),
            'region' => $billing_address->getAdministrativeArea(),
            'postal_code' => $billing_address->getPostalCode(),
          ),
        ),
        'card'=> array(
          'card_type' => $card_details[0],
          'account_number' => $card_details[1],
          'expire_month' => $card_details[2],
          'expire_year' => $card_details[3],
          'card_verification_value' => isset($card_details[4]) ? $card_details[4] : '',
        ),
      ]);
       if ($data->response->response_code !== 'A01') {
        throw new HardDeclineException('Could not charge the payment method. Response: ' . $data->response->response_desc, $data->response);
      }
      $capture = $this->getTxnType();
      $next_state = ($capture != "authorize") ? 'completed' : 'authorization';
      $payment->setState($next_state);
      if (!$capture) {
        $payment->setExpiresTime($this->time->getRequestTime() + (86400 * 29));
      }

      $payment
        ->setRemoteId($data->transaction_id.','.$data->response->authorization_code)
        ->setRemoteState('3')
        ->save();
    }
    catch (RequestException $e) {
      throw new HardDeclineException('Could not charge the payment method.');
    }
    
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->validatePayment($payment, 'authorization');
	  $payment_method = $payment->getPaymentMethod();   
    $this->assertPaymentMethod($payment_method);

    // Add a built in test for testing decline exceptions.
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billing_address */
    if ($billing_address = $payment_method->getBillingProfile()) {
      $billing_address = $payment_method->getBillingProfile()->get('address')->first();
    }
    else
    {
      throw new HardDeclineException('Address not Getting: ');
    }

    $capture_full_name = $billing_address->getGivenName().' '.$billing_address->getFamilyName();
    $respone_details = explode(',', $payment->remote_id->value);
    try {
      $data = $this->executeTransaction([
		'account_id' => 'act_' . $this->getRestAcctid(),
		'action' => 'Capture',
		'authorization_code' => $respone_details[1],
		'entered_by' => $capture_full_name,
    'transaction_id' => $respone_details[0],
      ]);

      if ($data->response->response_desc !== 'APPROVED') {
        throw new PaymentGatewayException('Count not capture payment. Message: ' . $data->response->response_desc, $data->response);
      }

      $payment->setState('completed');
      $payment->setAmount($amount);
      $payment->save();
    }
    catch (RequestException $e) {
      throw new PaymentGatewayException('Count not capture payment. Message: ' . $e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->validatePayment($payment, 'authorization');
	  $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);

    // Add a built in test for testing decline exceptions.
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billing_address */
    if ($billing_address = $payment_method->getBillingProfile()) {
      $billing_address = $payment_method->getBillingProfile()->get('address')->first();
    }
    else
    {
      throw new HardDeclineException('Address not Getting: ');
    }

    $capture_full_name = $billing_address->getGivenName().' '.$billing_address->getFamilyName();
    $respone_details = explode(',', $payment->remote_id->value);

    try {
      $data = $this->executeTransaction([
		'account_id' => 'act_' . $this->getRestAcctid(),
		'action' => 'void',
		'authorization_code' => $respone_details[1],
		'entered_by' => $capture_full_name,
    'transaction_id' => $respone_details[0],
      ]);

      if ($data->response->response_desc !== 'APPROVED') {
        throw new PaymentGatewayException('Payment could not be voided. Message: ' . $data->response->response_desc, $data->response);
      }

      $payment->setState('authorization_voided');
      $payment->save();
    }
    catch (RequestException $e) {
      throw new InvalidArgumentException('Only payments in the "authorization" state can be voided.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    try {
      
     $card_details = $payment_details['type'] . ',' . $payment_details['number'] . ',' . $payment_details['expiration']['month'] . ',' . $payment_details['expiration']['year'] . ',' . $payment_details['security_code'];      

      $payment_method->card_type = $payment_details['type'];
      // Only the last 4 numbers are safe to store.
      $payment_method->card_number = substr($payment_details['number'], -4);
      $payment_method->card_exp_month = $payment_details['expiration']['month'];
      $payment_method->card_exp_year = $payment_details['expiration']['year'];
      $expires = CreditCard::calculateExpirationTimestamp($payment_details['expiration']['month'], $payment_details['expiration']['year']);
     
     $credit_card_encode = base64_encode($card_details);
      // Store the remote ID returned by the request.
      $payment_method
        ->setRemoteId($credit_card_encode)
        ->setExpiresTime($expires)
        ->save();

      
    }
    catch (RequestException $e) {
      throw new HardDeclineException("Unable to store the credit card");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    $payment_method->delete();
  }

}

