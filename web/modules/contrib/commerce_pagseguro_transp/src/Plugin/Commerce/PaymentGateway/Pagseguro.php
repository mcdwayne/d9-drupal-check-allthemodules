<?php

namespace Drupal\commerce_pagseguro_transp\Plugin\Commerce\PaymentGateway;

// Drupal\commerce_pagseguro_transp\Plugin\Commerce\
// PaymentGateway\billing_address //.
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
use PagSeguro\Library;
use PagSeguro\Helpers\Xhr;
use PagSeguro\Configuration\Configure;
use PagSeguro\Services\Transactions\Cancel;
use PagSeguro\Services\Transactions\Refund;
use PagSeguro\Services\Transactions\Notification;
use PagSeguro\Domains\Requests\DirectPayment\Boleto;
use PagSeguro\Domains\Requests\DirectPayment\CreditCard;
use PagSeguro\Domains\Requests\DirectPayment\OnlineDebit;

/**
 * Provides the On-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "pagseguro_transp",
 *   label = @Translation("Pagseguro Transparente"),
 *   display_label = @Translation("Pagseguro Transparente"),
 *   forms = {
 *     "add-payment-method" =
 *   "Drupal\commerce_pagseguro_transp\PluginForm\PaymentMethodAddForm",
 *   },
 *   payment_type = "pagseguro_credit",
 *   js_library = "commerce_pagseguro_transp/commerce_pagseguro",
 *   payment_method_types = {
 *   "pagseguro_credit", "pagseguro_ticket", "pagseguro_debit"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard",
 *   "visa",
 *   },
 * )
 */
class Pagseguro extends OnsitePaymentGatewayBase implements PagseguroInterface, SupportsNotificationsInterface {

  protected $token;

  protected $email;

  protected $noInterestInstallmentQuantity;

  protected $emailBuyerSandbox;

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
    return $this->noInterestInstallmentQuantity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmailBuyer() {
    return $this->emailBuyerSandbox;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    // Setting properties by configuration.
    $this->noInterestInstallmentQuantity = $this->configuration['no_interest_installment_quantity'];
    $this->email = $this->configuration['email'];
    $this->emailBuyerSandbox = $this->configuration['email_buyer_sandbox'];

    $sandbox = ($this->getMode() == 'test');

    // Setting the correct token depending of the mode.
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
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $definitions = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions('user', 'user');

    // Getting all fields of user entity.
    $fields = [];
    /** @var \Drupal\Core\Field\FieldConfigBase $value */
    foreach ($definitions as $value) {
      if (method_exists($value, 'get') && $value->get('entity_type') == 'user') {
        $id = $value->getName();
        $name = $value->getLabel();
        $fields[$id] = $name;
      }
    }

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('A Pagseguro account valid email'),
      '#default_value' => $this->configuration['email'],
      '#required' => TRUE,
    ];
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The production token of Pagseguro'),
      '#default_value' => $this->configuration['token'],
      '#required' => TRUE,
    ];
    $form['token_sandbox'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The sandbox token of Pagseguro'),
      '#default_value' => $this->configuration['token_sandbox'],
      '#required' => FALSE,
    ];

    $form['email_buyer_sandbox'] = [
      '#type' => 'email',
      '#title' => $this->t('The sandbox buyer email of Pagseguro'),
      '#default_value' => $this->configuration['email_buyer_sandbox'],
      '#required' => FALSE,
    ];

    $form['no_interest_installment_quantity'] = [
      '#type' => 'select',
      '#title' => $this->t('No interest installment quantity'),
      '#options' => array_combine(range(2, 12), range(2, 12)),
      '#default_value' => $this->configuration['no_interest_installment_quantity'],
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
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $order = $payment->getOrder();
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $payment->getPaymentMethod();
    $payment_method_type = $payment_method->getType()->getPluginId();

    $response = $this->payPagseguro($order, $payment_method);

    // Payment complete.
    if ($response) {
      $this->assertPaymentState($payment, ['new']);
      $this->assertPaymentMethod($payment_method);

      if ($payment_method_type == 'pagseguro_debit' || $payment_method_type == 'pagseguro_ticket') {
        /** @var \PagSeguro\Parsers\Response\PaymentLink $response */
        $payment_method->set('payment_link', $response->getPaymentLink());
        $payment_method->save();
      }

      $payment->set('type', $payment_method_type);
      $payment->set('test', $this->getMode() == 'test');
      $payment->setState('new');
      /** @var \PagSeguro\Domains\Responses\PaymentMethod $response */
      $payment->setRemoteId($response->getCode());
      $payment->save();
    }
    // Error on payment.
    else {
      $payment_method->delete();
      throw new PaymentGatewayException('The provided payment method is no longer valid');
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
    // @todo Variable $cancel isn't used
    try {
      Cancel::create(
        Configure::getAccountCredentials(),
        $payment->getRemoteId()
      );

    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($e->getMessage());
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
      Refund::create(
        Configure::getAccountCredentials(),
        $payment->getRemoteId(),
        $amount
      );
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($e->getMessage());
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
    $expires = $this->time->getRequestTime() + (1296000);
    switch ($payment_method_type) {
      case 'pagseguro_credit':
        // The expected keys are payment gateway specific and usually match
        // the PaymentMethodAddForm form elements.
        // They are expected to be valid.
        $required_keys = [
          'holder_name', 'holder_cpf', 'card_token', 'installment_amount',
          'installments_qty', 'sender_hash',
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

        // Expires in 4 days.
        $expires = $this->time->getRequestTime() + (3600 * 96);
        break;

      case 'pagseguro_ticket':
        // Expires in 15 days.
        $payment_method->ticket_cpf = $payment_details['holder_cpf'];
        $expires = $this->time->getRequestTime() + (1296000);
        break;

      case 'pagseguro_debit':
        $payment_method->bank_name = $payment_details['banks'];
        // Expires in 2 days.
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
    // Delete the local entity.
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
   * @throws \Exception
   */
  public function onNotify(Request $request) {
    // @todo: In doc has a return statement below,
    // but function no returns anything
    // @return \Symfony\Component\HttpFoundation\Response|null
    // The response, or NULL to return an empty HTTP 200 response.
    // $request_data = $this->getRequestDataArray($request->getContent()); //.
    // $code = $request_data['notificationCode']; //.
    //
    // Access direct throw access denied.
    if (!$request->getContent()) {
      throw new AccessDeniedHttpException();
    }

    $this->initializePagseguro();

    // Call pagseguro service to check the transaction.
    try {
      if (Xhr::hasPost()) {
        $response = Notification::check(
          Configure::getAccountCredentials()
        );
      }
      else {
        throw new \InvalidArgumentException($_POST);
      }
    }
    catch (\Exception $e) {
      die($e->getMessage());
    }

    // Loading commerce_payment entity by pagseguro transaction code.
    $payments = \Drupal::entityTypeManager()
      ->getStorage('commerce_payment')
      ->loadByProperties(['remote_id' => $response->getCode()]);
    $payment = reset($payments);
    $status = $this->mapPagseguroStatus($response->getStatus());

    // If not specified, refund the entire amount.
    $amount = $payment->getAmount();
    $currency_code = $payment->getAmount()->getCurrencyCode();

    switch ($status) {
      case 'completed':
        $payment->setAmount(new Price($response->getGrossAmount(), $currency_code));
        break;

      case 'canceled':
        // If prior state was 'pending' the payment is a expired bank ticket.
        $status = ($payment->getState() == 'pending') ? 'expired' : 'canceled';
        break;

      case 'refunded':
        /** @var \Drupal\commerce_price\Price $old_refunded_amount */
        // Verify if partially refunded or total refunded.
        $old_refunded_amount = $payment->getRefundedAmount();
        $new_refunded_amount = $old_refunded_amount->add($amount);
        $status = $new_refunded_amount->lessThan($payment->getAmount()) ? 'partially_refunded' : 'refunded';
        $payment->setRefundedAmount($new_refunded_amount);
        break;
    }

    $payment->setState($status);
    $payment->save();
  }

  /**
   * Function to initalize pagseguro library.
   */
  private function initializePagseguro() {
    // Initializing pagseguro.
    try {
      Library::initialize();
    }
    catch (\Exception $e) {
      die($e->getMessage());
    }

    Library::cmsVersion()->setName("Nome")->setRelease("1.0.0");
    Library::moduleVersion()->setName("Nome")->setRelease("1.0.0");

    // UTF-8 or ISO-8859-1.
    Configure::setCharset('UTF-8');
    // Configure::setLog(true, '/pagseguro.log'); //.
    //
    // Production or sandbox.
    $environment = ($this->getMode() == 'test') ? 'sandbox' : 'production';
    Configure::setEnvironment($environment);
    Configure::setAccountCredentials(
      $this->getEmail(),
      $this->getToken()
    );
  }

  /**
   * Receives the order and payment method then sends to pagseguro.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order to be paid.
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   Payment method.
   *
   * @return int
   *   Return status code from pagseguro.
   */
  private function payPagseguro(OrderInterface $order,
                                 PaymentMethodInterface $payment_method) {
    try {
      $billing_profile = $order->getBillingProfile();
      $address = $billing_profile->get('address')->first();
      $sender_name = $address->getGivenName() . ' ' . $address->getAdditionalName() . ' ' . $address->getFamilyName();

      if (!empty($billing_profile->get('field_telephone')->first())) {
        $phone = commerce_pagseguro_transp_format_phone($billing_profile->get('field_telephone')
          ->first()
          ->getString());
      }

      // Initializing pagseguro.
      $this->initializePagseguro();

      $payment_method_type = $payment_method->getType()->getPluginId();

      switch ($payment_method_type) {
        case 'pagseguro_credit':
          // Instantiate a new direct payment request, using Credit Card.
          $payment_request = new CreditCard();

          // Get billing information for credit card.
          if (!empty($payment_method->getBillingProfile()->get('address')->first())) {
            /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $billing_address */
            $billing_address = $payment_method->getBillingProfile()->get('address')->first();

            // Set billing information for credit card.
            $street_name = commerce_pagseguro_transp_sanitize_string(
              $billing_address->getAddressLine1()
            );
            if ($billing_address) {
              $payment_request->setBilling()->setAddress()->withParameters(
                $street_name,
                '777',
                $billing_address->getDependentLocality(),
                $billing_address->getPostalCode(),
                $billing_address->getLocality(),
                $billing_address->getAdministrativeArea(),
                'BRA'
              );
            }
          }

          // Set the installment quantity and value
          // (could be obtained using the Installments service,
          // that have an example here in \public\getInstallments.php)
          if (!empty($payment_method->get('installments_qty')->first()->getString() && $payment_method->get('installment_amount')->first()->getString())) {
            $payment_request->setInstallment()->withParameters(
              $payment_method->get('installments_qty')->first()->getString(),
              number_format($payment_method->get('installment_amount')->first()->getString(), 2, '.', ''),
              (integer) $this->getNoInterestInstallmentQuantity()
            );
          }

          // Set the credit card holder information.
          // Birth date.
          if (!empty($billing_profile->get('field_birthdate')->first())) {
            $birthdate = date_create($billing_profile->get('field_birthdate')
              ->first()
              ->getString());
            $birthdate = date_format($birthdate, "d/m/Y");
            $payment_request->setHolder()->setBirthdate($birthdate);
          }

          // Holder name.
          if (!empty($payment_method->get('card_holder_name')->first())) {
            // Equals in Credit Card.
            $holder_name = commerce_pagseguro_transp_sanitize_string(
              $payment_method->get('card_holder_name')
                ->first()
                ->getString()
            );
            $payment_request->setHolder()->setName($holder_name);
          }

          // Document.
          if (!empty($payment_method->get('cpf')->first())) {
            $payment_request->setHolder()->setDocument()->withParameters(
              'CPF',
              $payment_method->get('cpf')->first()->getString()
            );
          }

          // Holder phone.
          if (isset($phone)) {
            $payment_request->setHolder()->setPhone()->withParameters(
              $phone['area_code'],
              (integer) $phone['phone']
            );
          }

          // Set credit card token.
          $payment_request->setToken($payment_method->getRemoteId());
          break;

        case 'pagseguro_ticket':
          // Instantiate a new Boleto Object.
          $payment_request = new Boleto();
          break;

        case 'pagseguro_debit':
          // Instantiate a new Boleto Object.
          $payment_request = new OnlineDebit();

          if (!empty($payment_method->get('bank_name')->first())) {
            $bank = $payment_method->get('bank_name')->first()->getString();
            // Set bank for this payment request.
            $payment_request->setBankName($bank);
          }
          break;
      }

      $payment_request->setReceiverEmail($this->getEmail());

      // Set a reference code for this payment request.
      // It is useful to identify this payment in future notifications.
      $payment_request->setReference($order->id());

      // Set the currency.
      $payment_request->setCurrency("BRL");

      // Add the items (products) for this payment request.
      foreach ($order->getItems() as $order_item) {
        $payment_request->addItems()->withParameters(
          $order_item->id(),
          $order_item->getTitle(),
          (integer) $order_item->getQuantity(),
          number_format($order_item->getUnitPrice()->getNumber(), 2, '.', '')
          // $order_item->setWeight($weight);
          // $order_item->setShippingCost($shippingCost);
        );
      }

      // Set extra amount.
      // $payment_request->setExtraAmount(11.5); //.
      //
      $sandbox = ($this->getMode() == 'test');

      // Set your customer information.
      // Set the correct information according the mode.
      if ($sandbox) {
        $payment_request->setSender()->setName('João Comprador');

        $email_buyer = $this->getEmailBuyer();
        if ($email_buyer) {
          $payment_request->setSender()->setEmail($email_buyer);
        }
        else {
          $payment_request->setSender()->setEmail('7links@sandbox.pagseguro.com.br');
        }
      }
      else {
        if (!empty($sender_name)) {
          $payment_request->setSender()->setName($sender_name);
        }
        $payment_request->setSender()->setEmail($order->getEmail());
      }

      // Sender phone.
      if (isset($phone)) {
        $payment_request->setSender()->setPhone()->withParameters(
          $phone['area_code'],
          $phone['phone']
        );
      }

      // Sender CPF.
      if (!empty($payment_method->get('cpf')->first())) {
        $payment_request->setSender()->setDocument()->withParameters(
          'CPF',
          $payment_method->get('cpf')->first()->getString()
        );
      }

      // Sender hash.
      if (!empty($payment_method->get('sender_hash')->first())) {
        $payment_request->setSender()->setHash($payment_method->get('sender_hash')
          ->first()
          ->getString());
      }
      // $payment_request->setSender()->setIp(\Drupal::request()->getClientIp());
      //
      //
      // Set shipping information for this payment request
      // Verify if commerce_shipping module is installed.
      // Tem que verificar também se existe algum produto enviável no pedido
      // if (\Drupal::moduleHandler()->moduleExists('commerce_shipping')) {
      //   // @todo: Descobrir como saber se o pedido ou algum de seus itens
      //   // tem envio e preencher os campos com as informações de envio.
      //   $payment_request->setShipping()->setAddress()->withParameters(
      //     'Av. Brig. Faria Lima',
      //     '1384',
      //     'Jardim Paulistano',
      //     '01452002',
      //     'São Paulo',
      //     'SP',
      //     'BRA',
      //     'apto. 114'
      //   );

      //   // @todo: verificar como pegar o tipo de frete que o usuário escolheu!
      //   // $payment_request->setShipping()->setCost()->withParameters(20.00);
      //   // $payment_request->setShipping()->setType()->withParameters(\PagSeguro\Enum\Shipping\Type::SEDEX);
      //   // Os tipos são:
      //   // PAC = 1;
      //   // SEDEX = 2;
      //   // NOT_SPECIFIED = 3;
      // }

      // // If there isn't shipping module, don´t need shipping information.
      // else {
      //   $payment_request->setShipping()->setAddressRequired()->withParameters('FALSE');
      // }

      // TODO: remover essa linha depois que descomentar as linhas de cima referente ao frete.
      $payment_request->setShipping()->setAddressRequired()->withParameters('FALSE');

      $adjustments = $order->getAdjustments();
      foreach ($adjustments as $adjustment) {
        $amount = number_format($adjustment->getAmount()->getNumber(), 2, '.', '');
        $payment_request->setShipping()->setCost()->withParameters($amount);
        $payment_request->setExtraAmount($amount);
      }

      // Set the Payment Mode for this payment request.
      $payment_request->setMode('DEFAULT');

      $payment_request->setRedirectUrl(
        \Drupal::request()->getHost() .
        \Drupal::request()->getBasePath()
      );

      $payment_gateway_id = $payment_method->getPaymentGatewayId();

      if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) {
        $protocol = 'https://';
      }
      else {
        $protocol = 'http://';
      }

      $payment_request->setNotificationUrl(
        $protocol .
        \Drupal::request()->getHost() .
        \Drupal::request()->getBasePath() .
        "/payment/notify/$payment_gateway_id"
      );
    }
    catch (MissingDataException $e) {
      \Drupal::messenger()->addError($e->getMessage());
    }

    // Send request payment to pagseguro.
    try {
      // Get the credentials and register payment.
      $result = $payment_request->register(
        Configure::getAccountCredentials()
      );
      return $result;
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($e->getMessage());

      return 0;
    }
  }

  /**
   * Convert pagseguro's status codes to translatable string status description.
   *
   * @todo Change return to a translatable string?
   *
   * @param int $status
   *   Status code returned by pagseguro.
   *
   * @return string
   *   String status description.
   */
  private function mapPagseguroStatus($status) {
    $return = '';
    switch ($status) {
      // t('Awaiting payment')
      case '1':
        $return = 'pending';
        break;

      // t('Under analysis')
      case '2':
        $return = 'processing';
        break;

      // t('Paid')
      case '3':
        $return = 'completed';
        break;

      // t('In dispute')
      case '5':
        $return = 'dispute';
        break;

      // t('Refunded')
      case '6':
        $return = 'refunded';
        break;

      // t('Canceled')
      case '7':
        $return = 'canceled';
        break;
    }
    return $return;
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

  /**
   * Convert pagseguro's error codes to translatable string error description.
   *
   * @param int $status
   *   Error code returned by pagseguro.
   *
   * @return string
   *   Translatable string error description.
   */
  private function mapPagseguroErrors($status) {
    switch ($status) {
      case 53004:
        $result = t('Items invalid quantity.');
        break;

      case 53005:
        $result = t('Currency is required.');
        break;

      case 53006:
        $result = t('Currency invalid value.');
        break;

      case 53007:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53008:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53009:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53010:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53011:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53012:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53013:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53014:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53015:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53017:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53018:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53019:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53020:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53021:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53022:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53023:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53024:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53025:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53026:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53027:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53028:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53029:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53030:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53031:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53032:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53033:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53034:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53035:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53036:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53037:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53038:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53039:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53040:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53041:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53042:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53043:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53044:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53045:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53046:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53047:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53048:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53049:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53050:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53051:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53052:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53053:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53054:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53055:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53056:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53057:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53058:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53059:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53060:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53061:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53062:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53063:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53064:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53065:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53066:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53067:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53068:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53069:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53070:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53071:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;

      case 53072:
        $result = t('Undefined error: @status on mapPagseguroErrors.', ['@status' => $status]);
        break;
    }

    return $result;
  }

}
