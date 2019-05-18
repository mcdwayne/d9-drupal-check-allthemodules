<?php

namespace Drupal\commerce_pagseguro_transp\PluginForm;

// @todo: Preencher o CPF com o campo CPF do perfil do usuário.
// @todo: resolver problema do inclusão do cupom de desconto.
// Quando ele é inserido, o valor do pedido tem que ser recalculado,
// assim como o recalculo das parcelas.

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\Order;
use PagSeguro\Library;
use PagSeguro\Configuration\Configure;
use PagSeguro\Services\Session;

/**
 * PaymentMethodAddForm.
 *
 * @todo Create a better description to this class.
 */
class PaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $payment_method = $this->entity;

    // Call the corret function for create forms according payment method.
    switch ($payment_method->bundle()) {
      case 'pagseguro_ticket':
        $form['payment_details'] = $this->buildTicketForm($form['payment_details'], $form_state);
        break;

      case 'pagseguro_credit':
        $form['payment_details'] = $this->buildCreditCardForm($form['payment_details'], $form_state);
        break;

      case 'pagseguro_debit':
        $form['payment_details'] = $this->buildDebitForm($form['payment_details'], $form_state);
        break;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function basePaymentForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_pagseguro_transp\Plugin\Commerce\PaymentGateway\PagseguroInterface $plugin */
    $plugin = $this->plugin;

    $sandbox = ($plugin->getMode() == 'test');
    $email = $plugin->getEmail();
    $token = $plugin->getToken();

    $amount = 0;
    // Loading order for loading total price and customer.
    $param = \Drupal::routeMatch()->getParameter('commerce_order');
    if (isset($param)) {
      $order_id = $param->id();
      $order = Order::load($order_id);
      $amount = $order->getTotalPrice()->getNumber();
    }

    $element['holder_cpf'] = [
      '#type' => 'cpf',
      '#title' => t('CPF'),
      '#required' => TRUE,
      '#mask' => TRUE,
      '#attributes' => [
        'autocomplete' => 'off',
        'id' => 'doc-number',
      ],
    ];

    // Hidden fields filled by pageseguro_transparente.js.
    $element['sender_hash'] = [
      '#type' => 'hidden',
      '#default_value' => '',
      '#attributes' => ['id' => 'sender-hash'],
    ];

    // Adding PagSeguro library according environment.
    if ($sandbox) {
      $element['#attached']['library'][] = 'commerce_pagseguro_transp/pagseguro_sandbox';
      $environment = 'sandbox';
      $this->fillFormTestValues($element);
    }
    else {
      $element['#attached']['library'][] = 'commerce_pagseguro_transp/pagseguro_production';
      $environment = 'production';
    }

    // Loading commerce_pagseguro_transp.js.
    $element['#attached']['library'][] = 'commerce_pagseguro_transp/commerce_pagseguro';

    // Call the function to initialize Pagseguro section.
    $session = $this->initializePagseguroSession($email, $token, $environment);

    // Passing the params session and amount to the .js.
    $element['#attached']['drupalSettings']['commercePagseguroTransparente']['commercePagseguro']['session'] = $session;
    $element['#attached']['drupalSettings']['commercePagseguroTransparente']['commercePagseguro']['amount'] = $amount;
    $element['#attached']['drupalSettings']['commercePagseguroTransparente']['commercePagseguro']['maxInstallmentNoInterest'] = (integer) $plugin->getNoInterestInstallmentQuantity();

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTicketForm(array $element, FormStateInterface $form_state) {
    $element['payment_type_tikcet'] = [
      '#type' => 'hidden',
      '#default_value' => 'pagseguro_ticket',
      '#attributes' => ['id' => 'payment-type'],
    ];

    return $this->basePaymentForm($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildDebitForm(array $element, FormStateInterface $form_state) {
    $element = $this->basePaymentForm($element, $form_state);

    // @todo: analisar necessidade de fazer um cadastro de bancos.
    $banks = [
      'bradesco' => 'Bradesco',
      'itau' => 'Itaú',
      'bancodobrasil' => 'Banco do Brasil',
      'banrisul' => 'Banrisul',
      'hsbc' => 'HSBC',
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

    // Editing field of credit card form to add better html ID.
    $element['number']['#attributes'] = ['id' => 'credit-card-number'];
    $element['number']['#required'] = FALSE;
    $element['expiration']['month']['#attributes'] = ['id' => 'expiration-month'];
    $element['expiration']['year']['#attributes'] = ['id' => 'expiration-year'];
    $element['security_code']['#attributes'] = ['id' => 'security-code'];
    $element['security_code']['#required'] = FALSE;

    // Creating new fields on credit card form.
    $element['installments'] = [
      '#type' => 'select',
      '#title' => t('Installments'),
      '#description' => t('Select the installments quantity.'),
      '#required' => FALSE,
      '#disabled' => TRUE,
      '#attributes' => ['id' => 'installments'],
    ];
    // Options to installments form.
    $element['installments']['#options'] = array_combine(range(1, 12), range(1, 12));
    asort($element['installments']['#options']);

    $element['holder_name'] = [
      '#type' => 'textfield',
      '#title' => t('Card Holder Name'),
      '#attributes' => [
        'autocomplete' => 'off',
        'id' => 'holder-name',
      ],
      '#required' => TRUE,
      '#description' => t('Enter the name printed on card.'),
    ];

    // @todo: quando um cupom de desconto for inserido,
    // atualizar o valor total passado para recalcular as parcelas.
    // Hidden fields filled by pageseguro_transparente.js.
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
   * Function to initalize pagseguro session.
   *
   * @param string $email
   *   Client's email.
   * @param string $token
   *   Stored client's token.
   * @param string $environment
   *   Production or sandbox.
   */
  public static function initializePagseguroSession($email, $token, $environment) {
    // @todo: In doc has a return statement below,
    // but function no returns anything
    // @return mixed
    Library::initialize();

    // To do a dynamic configuration of the library credentials you have
    // to use the set methods from the static class
    // \PagSeguro\Configuration\Configure.
    // Configure::setLog(true, '/logpath/logFilename.log'); //.
    // Production or sandbox.
    Configure::setEnvironment($environment);
    // UTF-8 or ISO-8859-1.
    Configure::setCharset('UTF-8');
    Configure::setAccountCredentials($email, $token);

    try {
      $session_code = Session::create(
        Configure::getAccountCredentials()
      );
      return $session_code->getResult();
    }
    catch (Exception $e) {
      die($e->getMessage());
    }
  }

  /**
   * Auxiliar function to fill a form with tests values.
   *
   * @param mixed $element
   *   Element form to fill.
   */
  public function fillFormTestValues(&$element) {
    $element['number']['#default_value'] = '4111111111111111';
    $element['expiration']['month']['#default_value'] = '12';
    $element['expiration']['year']['#options']['2030'] = '30';
    $element['expiration']['year']['#default_value'] = '2030';
    // $element['security_code']['#default_value'] = '123';
    $element['holder_cpf']['#default_value'] = '63410613315';
    $element['holder_name']['#default_value'] = 'Test Buyer';
  }

}
