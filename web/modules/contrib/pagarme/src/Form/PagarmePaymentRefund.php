<?php

namespace Drupal\pagarme\Form;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\pagarme\Helpers\PagarmeUtility;
use Drupal\pagarme\Pagarme\PagarmeDrupal;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PagarmePaymentDetail.
 * \Drupal\commerce_order\Entity\OrderInterface
 * @package Drupal\pagarme\Form
 */
class PagarmePaymentRefund extends FormBase {
  /**
   * Pagarme transaction id.
   *
   * @var PagarmeSdk transaction id
   */
  protected $transaction_id;
  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  /**
   * The database object.
   *
   * @var Drupal\commerce_order\Entity\Order
   */
  protected $order;
  /**
   * The database object.
   *
   * @var Drupal\commerce_payment\Entity\PaymentGateway
   */
  protected $payment_gateway;
  /**
   * Constructs a new PagarmePaymentDetail object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   */
  public function __construct(CurrentRouteMatch $current_route_match, Connection $database) {
    $this->transaction_id = $current_route_match->getParameter('transaction_id');
    $this->database = $database;
    $this->loadOrder()->loadPaymentGateWay();
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('database')
    );
  }
  /**
   * Method loadOrder
   *
   * @return object Load Drupal\commerce_order\Entity\Order class and return the updated object 
   */
  private function loadOrder() {
    $query = $this->database->select('pagarme_postback', 'pgp');
    $query->fields('pgp', ['order_id'])->condition('pagarme_id', $this->transaction_id);
    $order_id = $query->execute()->fetchField();
    $this->order = Order::load($order_id);
    return $this;
  }
  /**
   * Method loadPaymentGateWay
   *
   * @return void Load Drupal\commerce_payment\Entity\PaymentGateway 
   */
  private function loadPaymentGateWay() {
    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $payment_gateway */
    $payment_gateway = $this->order->get('payment_gateway');
    $this->payment_gateway = current($payment_gateway->referencedEntities());
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pagarme_payment_refund_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $payment_gateway */
    $plugin_config = $this->payment_gateway->get('configuration');
    $pagarmeDrupal = new PagarmeDrupal($plugin_config['pagarme_api_key']);
    $currency_code = $this->order->getTotalPrice()->getCurrencyCode();
    try {
      $transaction = $pagarmeDrupal->pagarme->transaction()->get($this->transaction_id);
      if ($transaction->getPaymentMethod() == 'boleto') {
        $form['bank_account'] = [
          '#type' => 'details',
          '#title' => $this->t('Confirmation - bank details'),
          '#open' => TRUE
        ];

        $form['bank_account']['bank_code'] = [
          '#type' => 'select',
          '#title' => $this->t('Bank code'),
          '#description' => $this->t("Recipient's bank code."),
          '#options' => \Drupal\pagarme\Helpers\PagarmeUtility::banks(),
          '#required' => TRUE,
        ];

        $form['bank_account']['type'] = [
          '#type' => 'select',
          '#title' => $this->t('Type of bank account.'),
          '#description' => $this->t('Type of bank account.'),
          '#options' => \Drupal\pagarme\Helpers\PagarmeUtility::accountTypes(),
          '#required' => TRUE,
        ];

        $form['bank_account']['legal_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Full name or company name'),
          '#description' => $this->t('Full name or business name of the recipient.'),
          '#required' => TRUE,
        ];

        $form['bank_account']['document_number'] = [
          '#type' => 'textfield',
          '#title' => $this->t('CPF or CNPJ'),
          '#description' => $this->t('CPF or CNPJ of the recipient.'),
          '#element_validate' => ['element_validate_integer_positive'],
          '#maxlength' => 14,
          '#size' => 14,
          '#required' => TRUE,
        ];

        $form['bank_account']['agencia'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Agency Number'),
          '#description' => $this->t('Recipient account agency.'),
          '#element_validate' => ['element_validate_integer_positive'],
          '#maxlength' => 5,
          '#size' => 5,
          '#required' => TRUE,
        ];

        $form['bank_account']['agencia_dv'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Agency Verifier Digit'),
          '#description' => $this->t("Checker's agency check digit."),
          '#maxlength' => 2,
          '#size' => 2,
        ];

        $form['bank_account']['conta'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Account number'),
          '#description' => $this->t("Recipient's bank account number."),
          '#element_validate' => ['element_validate_integer_positive'],
          '#maxlength' => 13,
          '#size' => 13,
          '#required' => TRUE,
        ];

        $form['bank_account']['conta_dv'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Account Verifier Digit'),
          '#description' => $this->t('Recipient account verifier digit.'),
          '#maxlength' => 2,
          '#size' => 2,
          '#required' => TRUE,
        ];
      } else {
        $form['transaction_info'] = [
          '#type' => 'details',
          '#title' => t('Confirmation'),
          '#open' => TRUE
        ];

        $form['transaction_info']['notification'] = [
          '#markup' => '<div><p>Tem certeza que deseja estornar essa transação</p><p><b>Essa operação é irreversível.<b></p></div>',
        ];

        $paid_amount = $transaction->getPaidAmount();
        $refunded_amount = $transaction->getRefundedAmount();
        $refund_amount = $paid_amount - $refunded_amount;
        $converted_value = PagarmeUtility::currencyAmountFormat($refund_amount, $currency_code, 'integer');
        $description = $this->t("Amount to be reversed. Maximum allowed value") . ' ' . $converted_value;
        $form['transaction_info']['refund'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Amount to be reversed'),
          '#description' => $description,
          '#default_value' => PagarmeUtility::amountIntToDecimal($refund_amount),
          '#required' => TRUE,
        ];
      }
      
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Confirm'),
      ];
    }
    catch (Exception $e) {
      \Drupal::logger('pagarme')->error($e->getMessage());
    }
    return $form;
  }
  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $form_values = $form_state->getValues();
      $refund_form_input = $form_values['refund'];
      $plugin_config = $this->payment_gateway->get('configuration');
      $pagarmeDrupal = new PagarmeDrupal($plugin_config['pagarme_api_key']);
      $transaction = $pagarmeDrupal->pagarme->transaction()->get($this->transaction_id);
      if (is_object($transaction) && $transaction->getPaymentMethod() == 'credit_card') {
        $paid_amount = $transaction->getPaidAmount();
        $refunded_amount = $transaction->getRefundedAmount();
        $refund_amount = $paid_amount - $refunded_amount;
        $refund_form_input = PagarmeUtility::amountDecimalToInt($refund_form_input);
        $form_state->setValue('refund', $refund_form_input);
        if ($refund_form_input > (int) $refund_amount) {
          $form_state->setError('refund', $this->t('Amount to be reversed is greater than allowed.'));
        }
      }
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_values = $form_state->getValues();
    $transaction_id = $form_values['transaction_id'];
    $refund_form_input = $form_values['refund'];
    $plugin_config = $this->payment_gateway->get('configuration');
    $pagarmeDrupal = new PagarmeDrupal($plugin_config['pagarme_api_key']);
    try {
      $transaction = $pagarmeDrupal->pagarme->transaction()->get($this->transaction_id);
      switch ($transaction->getPaymentMethod()) {
        case 'boleto':
          $data_bank_account = [
            'bankCode' => $form_values["bank_code"],
            'type' => $form_values["type"],
            'legalName' => $form_values["legal_name"],
            'documentNumber' => $form_values["document_number"],
            'agencia' => $form_values["agencia"],
            'agenciaDv' => $form_values["agencia_dv"],
            'conta' => $form_values["conta"],
            'contaDv' => $form_values["conta_dv"],
          ];
          $bank_account = new \PagarMe\Sdk\BankAccount\BankAccount($data_bank_account);
          $refunded_transaction = $pagarmeDrupal->pagarme->transaction()->boletoRefund(
              $transaction,
              $bank_account
          );
          break;
        case 'credit_card':
          $refunded_transaction = $pagarmeDrupal->pagarme->transaction()->creditCardRefund(
              $transaction,
              $refund_form_input
          );
          break;
      }
      drupal_set_message(t('Reversal made successfully.'));
    }
    catch (\PagarMe\Sdk\ClientException $e) {
      $response = json_decode(json_decode($e->getMessage()));
      $errors = [];
      if (!empty($response->errors)) {
        foreach ($response->errors as $key => $error) {
          $parameter_name = ($error->parameter_name) ? $error->parameter_name . ': ' : '';
          $errors[] = t($parameter_name) . t($error->message);
        }
      }
      $item_list = [
        '#theme' => 'item_list',
        '#items' => $errors,
      ];
      $theme_item_list = drupal_render($item_list);
      drupal_set_message($theme_item_list, 'error');
    } 
    catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
  }
}
