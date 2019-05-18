<?php

namespace Drupal\commerce_pagseguro\PluginForm;

use Drupal\commerce_pagseguro_transp\Plugin\Commerce\PaymentGateway\Pagseguro;
use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\Order;
use PagSeguro\Library;
use PagSeguro\Configuration;
use PagSeguro\Services;

class PaymentMethodAddForm extends BasePaymentMethodAddForm  {
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $payment_method = $this->entity;

    // Create the form for the selected payment method.
    switch ($payment_method->bundle()) {
      case 'pagseguro_boleto':
        $form['payment_details'] = $this->buildTicketForm($form['payment_details'], $form_state);
        break;
      case 'pagseguro_credit_card':
        $form['payment_details'] = $this->buildCreditCardForm($form['payment_details'], $form_state);
        break;
      case 'pagseguro_debit':
        $form['payment_details'] = $this->buildDebitForm($form['payment_details'], $form_state);
        break;
      case 'pagseguro_lightbox':
        $this->buildLightboxForm($form, $form_state);
        break;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function basePaymentForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_pagseguro_transp\Plugin\Commerce\PaymentGateway\PagseguroInteface $plugin */
    $plugin = $this->plugin;

    $sandbox = ($plugin->getMode() == 'test');
    $email = $plugin->getEmail();
    $token = $plugin->getToken();
    $amount = 0;

    // Loading order to retrieve total price and customer.
    $param = \Drupal::routeMatch()->getParameter('commerce_order');
    if (isset($param)) {
      $order_id = $param->id();
      $order = Order::load($order_id);
      $amount = $order->getTotalPrice()->getNumber();

      //G et the name of the field that contains the user's CPF.
      $field_cpf = $plugin->getFieldCpf();
      $cpf = '';
      if ($field_cpf) {
        /** @var \Drupal\user\UserInterface $customer */
        $customer = $order->getCustomer();
        if (!empty($customer->get($field_cpf)->first())) {
          $cpf = $customer->get($field_cpf)->first()->getString();
        }
      }
    }

    $element['holder_cpf'] = [
      '#type' => 'cpf',
      '#title' => t('CPF'),
      '#default_value' => $cpf,
      '#attributes' => [
        'autocomplete' => 'off',
        'id' => 'doc-number'
      ],
      '#required' => TRUE,
      '#mask' => TRUE,
    ];

    // Hidden fields filled by pageseguro_transparente.js.
    $element['sender_hash'] = [
      '#type' => 'hidden',
      '#default_value' => '',
      '#attributes' => ['id' => 'sender-hash'],
    ];

    // Adding PagSeguro library according environment.
    if ($sandbox) {
      $element['#attached']['library'][] = 'commerce_pagseguro/pagseguro_sandbox';
      $environment = 'sandbox';
      $this->fillFormTestValues($element);
    } else {
      $element['#attached']['library'][] = 'commerce_pagseguro/pagseguro_production';
      $environment = 'production';
    }

    // Call the function to initialize Pagseguro section.
    $session = $this->initializePagseguroSession($email, $token, $environment);

    // Passing the params session and amount to the .js
    $element['#attached']['drupalSettings']['commercePagseguro']['sessionId'] = $session;
    $element['#attached']['drupalSettings']['commercePagseguro']['orderAmount'] = $amount;
    $element['#attached']['drupalSettings']['commercePagseguro']['maxInstallmentNoInterest'] = (integer) $plugin->getNoInterestInstallmentQuantity();

    return $element;
  }

  public function buildTicketForm(array $element, FormStateInterface $form_state) {
    $element['payment_type_boleto'] = [
      '#type' => 'hidden',
      '#default_value' => 'pagseguro_boleto',
      '#attributes' => ['id' => 'payment-type'],
    ];

    return $this->basePaymentForm($element, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  protected function buildDebitForm(array $element, FormStateInterface $form_state) {
    $element = $this->basePaymentForm($element, $form_state);

    $banks = [
      'bradesco' => 'Bradesco',
      'itau' => 'Itaú',
      'bancodobrasil' => 'Banco do Brasil',
      'banrisul' => 'Banrisul',
      'hsbc' => 'HSBC'
    ];

    $element['banks'] = [
      '#type' => 'select',
      '#title' => t('Banks'),
      '#options' => $banks,
      '#description' => t('Select the bank'),
      '#required' => TRUE,
      '#attributes' => ['id' => 'banks'],
    ];

    $element['payment_type_debit'] = [
      '#type' => 'hidden',
      '#default_value' => 'pagseguro_debit',
      '#attributes' => ['id' => 'payment-type'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    $element = parent::buildCreditCardForm($element, $form_state);

    $amount = 0;
    // Loading order for loading total price.
    $param = \Drupal::routeMatch()->getParameter('commerce_order');
    if (isset($param)) {
      $order_id = $param->id();
      $order = Order::load($order_id);
      $amount = $order->getTotalPrice()->getNumber();
    }

    // Add HTML ids that are easier to manipulate.
    $element['number']['#attributes'] = ['id' => 'credit-card-number'];
    $element['expiration']['month']['#attributes'] = ['id' => 'expiration-month'];
    $element['expiration']['year']['#attributes'] = ['id' => 'expiration-year'];
    $element['security_code']['#attributes'] = ['id' => 'security-code'];

    // Create new fields on credit card form.
    $element['installments'] = [
      '#type' => 'select',
      '#title' => t('Installments'),
      '#options' => [-1 => 'Select the installments quantity...', '01' => '01', '02' => '02', '03' => '03', '04' => '04', '05' => '05', '06' => '06', '07' => '07', '08' => '08', '09' => '09', '10' => '10', '11' => '11', '12' => '12'],
      '#description' => t('Select the installments quantity.'),
      '#required' => FALSE,
      '#disabled' => TRUE,
      '#attributes' => ['id' => 'installments'],
    ];

    $element['holder_name'] = [
      '#type' => 'textfield',
      '#title' => t('Card Holder Name'),
      '#attributes' => [
        'autocomplete' => 'off',
        'id' => 'holder-name'
      ],
      '#required' => TRUE,
    ];

    // Hidden fields filled by pageseguro_transparente.js
    $element['installments_qty'] = [
      '#type' => 'hidden',
      '#default_value' => 1,
      '#attributes' => ['id' => 'installments-qty'],
    ];

    $element['installment_amount'] = [
      '#type' => 'hidden',
      '#default_value' => $amount,
      '#attributes' => ['id' => 'installment-amount'],
    ];

    $element['card_token'] = [
      '#type' => 'hidden',
      '#default_value' => '',
      '#attributes' => ['id' => 'card-token'],
    ];

    $element = $this->basePaymentForm($element, $form_state);

    $element['payment_type_credit'] = [
      '#type' => 'hidden',
      '#default_value' => 'pagseguro_credit',
      '#attributes' => ['id' => 'payment-type'],
    ];

    $element['payment_method_id'] = [
      '#type' => 'hidden',
      '#default_value' => '',
      '#attributes' => ['id' => 'payment-method-id'],
      '#prefix' => '<div class="pagseguro-stamp"></div>',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildLightboxForm(array &$form, FormStateInterface $form_state) {
    $form['payment_details'] = $this->basePaymentForm($form['payment_details'], $form_state);
  }

  /**
   * @param $email
   * @param $token
   * @param $environment
   *
   * @return mixed
   * @throws \Exception
   */
  public static function initializePagseguroSession($email, $token, $environment) {
    \PagSeguro\Library::initialize();

    // Envrionment options: Production or sandbox.
    \PagSeguro\Configuration\Configure::setEnvironment($environment);

    \PagSeguro\Configuration\Configure::setAccountCredentials($email, $token);
    // Charset options: UTF-8 or ISO-8859-1.
    \PagSeguro\Configuration\Configure::setCharset('UTF-8');

    try {
      $session_code = \PagSeguro\Services\Session::create(
        \PagSeguro\Configuration\Configure::getAccountCredentials()
      );
      return $session_code->getResult();
    } catch (Exception $e) {
      die($e->getMessage());
    }
  }

  public function fillFormTestValues(&$element) {
    $element['number']['#default_value'] = '4111111111111111';
    $element['expiration']['month']['#default_value'] = '11';
    $element['expiration']['year']['#options']['2040'] = '20';
    $element['expiration']['year']['#default_value'] = '2040';
    $element['security_code']['#default_value'] = '123';
    $element['holder_cpf']['#default_value'] = '64075849813';
    $element['holder_name']['#default_value'] = 'Test Customer';
  }

}