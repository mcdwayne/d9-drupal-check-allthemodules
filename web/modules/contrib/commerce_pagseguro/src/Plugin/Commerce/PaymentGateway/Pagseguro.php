<?php

namespace Drupal\commerce_pagseguro\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\commerce_pagseguro\Plugin\Commerce\PaymentGateway\PagseguroMappings;

/**
 * Provides the On-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "pagseguro_checkout_transparente",
 *   label = @Translation("PagSeguro"),
 *   display_label = @Translation("PagSeguro"),
 *   forms = {
 *     "add-payment-method" =
 *   "Drupal\commerce_pagseguro\PluginForm\PaymentMethodAddForm",
 *   },
 *   payment_type = "pagseguro_credit_card",
 *   js_library = "commerce_pagseguro/commerce_pagseguro",
 *   payment_method_types = {"pagseguro_credit_card", "pagseguro_boleto", "pagseguro_debit", "pagseguro_lightbox"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard",
 *   "visa",
 *   },
 * )
 */
class Pagseguro extends OnsitePaymentGatewayBase implements PagseguroInterface, SupportsNotificationsInterface {

  protected $token;

  protected $email;

  protected $no_interest_installment_quantity;

  protected $email_buyer_sandbox;

  protected $field_birthdate;

  protected $field_cpf;

  protected $field_full_name;

  protected $field_telephone;

  private $payment_method;

  private $payment_request;

  private $customer;

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    return $this->token;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * {@inheritdoc}
   */
  public function getNoInterestInstallmentQuantity() {
    return $this->no_interest_installment_quantity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmailBuyer() {
    return $this->email_buyer_sandbox;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldCpf() {
    return $this->field_cpf;
  }



  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
   
    // Setting properties by configuration.
    $this->no_interest_installment_quantity = $this->configuration['no_interest_installment_quantity'];
    $this->email = $this->configuration['email'];
    $this->email_buyer_sandbox = $this->configuration['email_buyer_sandbox'];
    $this->field_birthdate = $this->configuration['field_birthdate'];
    $this->field_cpf = $this->configuration['field_cpf'];
    $this->field_full_name = $this->configuration['field_full_name'];
    $this->field_telephone = $this->configuration['field_telephone'];

    // Setting the token to live or test.
    $sandbox = ($this->getMode() == 'test');
    if ($sandbox) {
      $this->token = $this->configuration['token_sandbox'];
    }
    else {
      $this->token = $this->configuration['token'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'email' => '',
        'token' => '',
        'no_interest_installment_quantity' => '',
        'token_sandbox' => '',
        'email_buyer_sandbox' => '',
        'field_birthdate' => '',
        'field_cpf' => '',
        'field_full_name' => '',
        'field_telephone' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $definitions = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions('user', 'user');

    //$definitions += \Drupal::service('entity_field.manager')
    //->getFieldDefinitions('commerce_order', 'commerce_order');

    //Getting all fields of user entity
    $fields = [];
    /** @var  \Drupal\Core\Field\FieldConfigBase $value */
    foreach ($definitions as $key => $value) {
      if (method_exists($value, 'get') && $value->get('entity_type') == 'user') {
        $id = $value->getName();
        $name = $value->getLabel();
        $fields[$id] = $name;
      }
    }

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('A valid email for the Pagseguro account.'),
      '#default_value' => $this->configuration['email'],
      '#required' => TRUE,
    ];
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The production token of the Pagseguro account.'),
      '#default_value' => $this->configuration['token'],
      '#required' => TRUE,
    ];
    $form['token_sandbox'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The sandbox token of the Pagseguro account.'),
      '#default_value' => $this->configuration['token_sandbox'],
      '#required' => FALSE,
    ];

    $form['email_buyer_sandbox'] = [
      '#type' => 'email',
      '#title' => $this->t('The sandbox buyer email of the Pagseguro account.'),
      '#default_value' => $this->configuration['email_buyer_sandbox'],
      '#required' => FALSE,
    ];

    $form['no_interest_installment_quantity'] = [
      '#type' => 'select',
      '#title' => $this->t('No interest installment quantity'),
      '#options' => [2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11, 12 => 12],
      '#default_value' => $this->configuration['no_interest_installment_quantity'],
      '#required' => TRUE,
    ];

    $form['field_birthdate'] = [
      '#type' => 'select',
      '#options' => $fields,
      '#empty_option' => t('-- Select an option --'),
      '#title' => $this->t('Birthdate Field on User'),
      '#default_value' => $this->configuration['field_birthdate'],
      '#required' => TRUE,
    ];

    $form['field_cpf'] = [
      '#type' => 'select',
      '#options' => $fields,
      '#empty_option' => t('-- Select an option --'),
      '#title' => $this->t('CPF Field on User'),
      '#default_value' => $this->configuration['field_cpf'],
      '#required' => TRUE,
    ];

    $form['field_full_name'] = [
      '#type' => 'select',
      '#options' => $fields,
      '#empty_option' => t('-- Select an option --'),
      '#title' => $this->t('Full Name Field on User'),
      '#default_value' => $this->configuration['field_full_name'],
      '#required' => TRUE,
    ];

    $form['field_telephone'] = [
      '#type' => 'select',
      '#options' => $fields,
      '#empty_option' => t('-- Select an option --'),
      '#title' => $this->t('Telephone Field on User'),
      '#default_value' => $this->configuration['field_telephone'],
      '#required' => TRUE,
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
      $this->configuration['email'] = $values['email'];
      $this->configuration['token'] = $values['token'];
      $this->configuration['no_interest_installment_quantity'] = (integer) $values['no_interest_installment_quantity'];
      $this->configuration['token_sandbox'] = $values['token_sandbox'];
      $this->configuration['email_buyer_sandbox'] = $values['email_buyer_sandbox'];
      $this->configuration['field_birthdate'] = $values['field_birthdate'];
      $this->configuration['field_cpf'] = $values['field_cpf'];
      $this->configuration['field_full_name'] = $values['field_full_name'];
      $this->configuration['field_telephone'] = $values['field_telephone'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['email'] = $values['email'];
      $this->configuration['token'] = $values['token'];
      $this->configuration['no_interest_installment_quantity'] = (integer) $values['no_interest_installment_quantity'];
      $this->configuration['token_sandbox'] = $values['token_sandbox'];
      $this->configuration['email_buyer_sandbox'] = $values['email_buyer_sandbox'];
      $this->configuration['field_birthdate'] = $values['field_birthdate'];
      $this->configuration['field_cpf'] = $values['field_cpf'];
      $this->configuration['field_full_name'] = $values['field_full_name'];
      $this->configuration['field_telephone'] = $values['field_telephone'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->order = $payment->getOrder();
    //exit(print_r($this->order));
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $this->payment_method = $payment->getPaymentMethod();
    //$this->customer = $order->getCustomer();

    $payment_method_type = $this->payment_method->getType()->getPluginId();
    $payment_handler = new PagseguroPaymentHandler($this->order, $this->payment_method);

    // Send the payment to PagSeguro.
    if ($payment_method_type != 'pagseguro_lightbox') {
      $response = $payment_handler->payPagseguro();
      if ($response) {
        $this->assertPaymentState($payment, ['new']);
        $this->assertPaymentMethod($this->payment_method);
        $payment_handler->registerPaymentTransaction($payment, $response);
      }
    }
    else {
      $payment_handler->registerLightboxPaymentTransaction($payment);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {

  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);

    $this->initializePagseguro();

    // Call pagseguro service do cancel transaction.
    try {
      $cancel = \PagSeguro\Services\Transactions\Cancel::create(
        \PagSeguro\Configuration\Configure::getAccountCredentials(),
        $payment->getRemoteId()
      );

    } catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    $payment->setState('authorization_voided');
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    $this->initializePagseguro();

    // Call pagseguro service to refund payment.
    try {
      \PagSeguro\Services\Transactions\Refund::create(
        \PagSeguro\Configuration\Configure::getAccountCredentials(),
        $payment->getRemoteId(),
        $amount
      );
    } catch (Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    // Verify if partially refunded or total refunded.
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

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method,
                                      array $payment_details) {

    $payment_method_type = $payment_method->getType()->getPluginId();
    switch ($payment_method_type) {
      case 'pagseguro_credit_card':
        $required_keys = [
          // The expected keys are payment gateway specific and usually match
          // the PaymentMethodAddForm form elements. They are expected to be valid.
          'holder_name', 'holder_cpf', 'card_token', 'installment_amount',
          'installments_qty', 'sender_hash'
        ];

        foreach ($required_keys as $required_key) {
          if (empty($payment_details[$required_key])) {
            throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
          }
        }

        $payment_method->card_type = $payment_details['payment_method_id'];
        $payment_method->card_number = substr($payment_details['number'], -4);
        $payment_method->card_exp_month = $payment_details['expiration']['month'];
        $payment_method->card_exp_year = $payment_details['expiration']['year'];
        $payment_method->card_holder_name = $payment_details['holder_name'];
        $payment_method->installment_amount = $payment_details['installment_amount'];
        $payment_method->installments_qty = $payment_details['installments_qty'];
        $payment_method->setRemoteId($payment_details['card_token']);
        
        //Expires in 4 days
        $expires = $this->time->getRequestTime() + (3600 * 96);
        break;

      case 'pagseguro_boleto':
        // Expires in 7 days.
        // Todo: Make Boleto expiry date configurable.
        $payment_method->ticket_cpf = $payment_details['holder_cpf'];
        $expires = $this->time->getRequestTime() + (604800);
        break;

      case 'pagseguro_debit':
        $payment_method->bank_name = $payment_details['banks'];
        //Expires in 2 days
        $expires = $this->time->getRequestTime() + (3600 * 48);
        break;
    }

    $payment_method->cpf = $payment_details['holder_cpf'];
    $payment_method->sender_hash = $payment_details['sender_hash'];
    $payment_method->setExpiresTime($expires);
    $payment_method->setReusable(FALSE);
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // Delete the remote record here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    $payment_method->delete();
  }

  /**
   * Processes the "notify" request.
   *
   * Note:
   * This method can't throw exceptions on failure because some payment
   * providers expect an error response to be returned in that case.
   * Therefore, the method can log the error itself and then choose which
   * response to return.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response|null
   *   The response, or NULL to return an empty HTTP 200 response.
   * @throws \Exception
   */
  public function onNotify(Request $request) {
    //    $request_data = $this->getRequestDataArray($request->getContent());
    //    $code = $request_data['notificationCode'];

    //Access direct throw access denied.
    if (!$request->getContent()) {
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }

    $this->initializePagseguro();

    //Call pagseguro service to check the transaction
    try {
      if (\PagSeguro\Helpers\Xhr::hasPost()) {
        $response = \PagSeguro\Services\Transactions\Notification::check(
          \PagSeguro\Configuration\Configure::getAccountCredentials()
        );
      } else {
        throw new \InvalidArgumentException($_POST);
      }
    } catch (\Exception $e) {
      die($e->getMessage());
    }

    //Loading commerce_payment entity by pagseguro transaction code.
    $payments = \Drupal::entityTypeManager()
      ->getStorage('commerce_payment')
      ->loadByProperties(['remote_id' => $response->getCode()]);
    $payment = reset($payments);
    $status = PagseguroMappings::mapPagseguroStatus($response->getStatus());

    // If not specified, refund the entire amount.
    $amount = $payment->getAmount();

    switch ($status) {
      case 'paid':
        $payment->setAmount($response->getGrossAmount());
        break;
      case 'canceled':
        // If prior state was 'pending' the payment is a expired bank ticket
        $status = ($payment->getState() == 'pending') ? 'expired' : 'canceled';
        break;
      case 'refunded':
        /** @var \Drupal\commerce_price\Price $old_refunded_amount */
        //Verify if partially refunded or total refunded
        $old_refunded_amount = $payment->getRefundedAmount();
        $new_refunded_amount = $old_refunded_amount->add($amount);
        $status = $new_refunded_amount->lessThan($payment->getAmount()) ? 'partially_refunded' : 'refunded';
        $payment->setRefundedAmount($new_refunded_amount);
        break;
    }

    $payment->setState($status);
    $payment->save();
  }

  private function initializePagseguro() {
    // Initializing pagseguro
    try {
      \PagSeguro\Library::initialize();
    } catch (\Exception $e) {
      die($e->getMessage());
    }

    \PagSeguro\Library::cmsVersion()->setName("Nome")->setRelease("1.0.0");
    \PagSeguro\Library::moduleVersion()->setName("Nome")->setRelease("1.0.0");
    // UTF-8 or ISO-8859-1
    \PagSeguro\Configuration\Configure::setCharset('UTF-8');
    //\PagSeguro\Configuration\Configure::setLog(true, '/pagseguro.log');

    $environment = ($this->getMode() == 'test') ? 'sandbox' : 'production';
    // Production or sandbox.
    \PagSeguro\Configuration\Configure::setEnvironment($environment);
    \PagSeguro\Configuration\Configure::setAccountCredentials(
      $this->getEmail(),
      $this->getToken()
    );
  }

  /**
   * Get data array from a request content.
   *
   * @param string $request_content
   *   The Request content.
   *
   * @return array
   *   The request data array.
   */
  protected function getRequestDataArray($request_content) {
    parse_str(html_entity_decode($request_content), $request_data);
    return $request_data;
  }
}