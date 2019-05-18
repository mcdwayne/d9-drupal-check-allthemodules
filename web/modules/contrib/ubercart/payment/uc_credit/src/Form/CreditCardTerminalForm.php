<?php

namespace Drupal\uc_credit\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the credit card terminal form for administrators.
 */
class CreditCardTerminalForm extends FormBase {

  /**
   * The order that is being processed.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $order;

  /**
   * The payment method that is in use.
   *
   * @var \Drupal\uc_payment\PaymentMethodInterface
   */
  protected $paymentMethod;

  /**
   * The date.formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Form constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date.formatter service.
   */
  public function __construct(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_credit_terminal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL, PaymentMethodInterface $uc_payment_method = NULL) {
    $this->order = $uc_order;
    $this->paymentMethod = $uc_payment_method;

    // Get the transaction types available to our default gateway.
    $types = $this->paymentMethod->getPlugin()->getTransactionTypes();

    $balance = uc_payment_balance($this->order);

    $form['order_total'] = [
      '#prefix' => '<div><strong>',
      '#markup' => $this->t('Order total: @total', ['@total' => uc_currency_format($this->order->getTotal())]),
      '#suffix' => '</div></strong>',
    ];
    $form['balance'] = [
      '#prefix' => '<div><strong>',
      '#markup' => $this->t('Balance: @balance', ['@balance' => uc_currency_format($balance)]),
      '#suffix' => '</div></strong>',
    ];

    // Let the administrator set the amount to charge.
    $form['amount'] = [
      '#type' => 'uc_price',
      '#title' => $this->t('Charge Amount'),
      '#default_value' => $balance > 0 ? uc_currency_format($balance, FALSE, FALSE, '.') : 0,
    ];

    // Build a credit card form.
    $form['specify_card'] = [
      '#type' => 'details',
      '#title' => $this->t('Credit card details'),
      '#description' => $this->t('Use the available buttons in this fieldset to process with the specified card details.'),
      '#open' => TRUE,
    ];
    $form['specify_card']['cc_data'] = [
      '#tree' => TRUE,
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>',
    ];
    $form['specify_card']['cc_data'] += $this->paymentMethod->getPlugin()->cartDetails($this->order, [], $form_state);
    unset($form['specify_card']['cc_data']['cc_policy']);

    $form['specify_card']['actions'] = ['#type' => 'actions'];

    // If available, let the card be charged now.
    if (in_array(UC_CREDIT_AUTH_CAPTURE, $types)) {
      $form['specify_card']['actions']['charge_card'] = [
        '#type' => 'submit',
        '#value' => $this->t('Charge amount'),
      ];
    }

    // If available, let the amount be authorized.
    if (in_array(UC_CREDIT_AUTH_ONLY, $types)) {
      $form['specify_card']['actions']['authorize_card'] = [
        '#type' => 'submit',
        '#value' => $this->t('Authorize amount only'),
      ];
    }

    // If available, create a reference at the gateway.
    if (in_array(UC_CREDIT_REFERENCE_SET, $types)) {
      $form['specify_card']['actions']['reference_set'] = [
        '#type' => 'submit',
        '#value' => $this->t('Set a reference only'),
      ];
    }

    // If available, create a reference at the gateway.
    if (in_array(UC_CREDIT_CREDIT, $types)) {
      $form['specify_card']['actions']['credit_card'] = [
        '#type' => 'submit',
        '#value' => $this->t('Credit amount to this card'),
      ];
    }

    // Find any uncaptured authorizations.
    $options = [];

    if (isset($this->order->data->cc_txns['authorizations'])) {
      foreach ($this->order->data->cc_txns['authorizations'] as $auth_id => $data) {
        if (empty($data['captured'])) {
          $options[$auth_id] = $this->t('@auth_id - @date - @amount authorized', ['@auth_id' => strtoupper($auth_id), '@date' => $this->dateFormatter->format($data['authorized'], 'short'), '@amount' => uc_currency_format($data['amount'])]);
        }
      }
    }

    // If any authorizations existed...
    if (!empty($options)) {
      // Display fieldset with the authorizations and available action buttons.
      $form['authorizations'] = [
        '#type' => 'details',
        '#title' => $this->t('Prior authorizations'),
        '#description' => $this->t('Use the available buttons in this fieldset to select and act on a prior authorization. The charge amount specified above will be captured against the authorization listed below. Only one capture is possible per authorization, and a capture for more than the amount of the authorization may result in additional fees to you.'),
        '#open' => TRUE,
      ];

      $form['authorizations']['select_auth'] = [
        '#type' => 'radios',
        '#title' => $this->t('Select authorization'),
        '#options' => $options,
      ];

      $form['authorizations']['actions'] = ['#type' => 'actions'];

      // If available, capture a prior authorization.
      if (in_array(UC_CREDIT_PRIOR_AUTH_CAPTURE, $types)) {
        $form['authorizations']['actions']['auth_capture'] = [
          '#type' => 'submit',
          '#value' => $this->t('Capture amount to this authorization'),
        ];
      }

      // If available, void a prior authorization.
      if (in_array(UC_CREDIT_VOID, $types)) {
        $form['authorizations']['actions']['auth_void'] = [
          '#type' => 'submit',
          '#value' => $this->t('Void authorization'),
        ];
      }

      // Collapse this fieldset if no actions are available.
      if (!isset($form['authorizations']['actions']['auth_capture']) && !isset($form['authorizations']['actions']['auth_void'])) {
        $form['authorizations']['#open'] = FALSE;
      }
    }

    // Find any uncaptured authorizations.
    $options = [];

    if (isset($this->order->data->cc_txns['references'])) {
      foreach ($this->order->data->cc_txns['references'] as $ref_id => $data) {
        $options[$ref_id] = $this->t('@ref_id - @date - (Last 4) @card', ['@ref_id' => strtoupper($ref_id), '@date' => $this->dateFormatter->format($data['created'], 'short'), '@card' => $data['card']]);
      }
    }

    // If any references existed...
    if (!empty($options)) {
      // Display fieldset with the authorizations and available action buttons.
      $form['references'] = [
        '#type' => 'details',
        '#title' => $this->t('Customer references'),
        '#description' => $this->t('Use the available buttons in this fieldset to select and act on a customer reference.'),
        '#open' => TRUE,
      ];

      $form['references']['select_ref'] = [
        '#type' => 'radios',
        '#title' => $this->t('Select references'),
        '#options' => $options,
      ];

      $form['references']['actions'] = ['#type' => 'actions'];

      // If available, capture a prior references.
      if (in_array(UC_CREDIT_REFERENCE_TXN, $types)) {
        $form['references']['actions']['ref_capture'] = [
          '#type' => 'submit',
          '#value' => $this->t('Charge amount to this reference'),
        ];
      }

      // If available, remove a previously stored reference.
      if (in_array(UC_CREDIT_REFERENCE_REMOVE, $types)) {
        $form['references']['actions']['ref_remove'] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove reference'),
        ];
      }

      // If available, remove a previously stored reference.
      if (in_array(UC_CREDIT_REFERENCE_CREDIT, $types)) {
        $form['references']['actions']['ref_credit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Credit amount to this reference'),
        ];
      }

      // Collapse this fieldset if no actions are available.
      if (!isset($form['references']['actions']['ref_capture']) && !isset($form['references']['actions']['ref_remove']) && !isset($form['references']['actions']['ref_credit'])) {
        $form['references']['#open'] = FALSE;
      }
    }

    $form['#attached']['library'][] = 'uc_credit/uc_credit.styles';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the data from the form and replace masked data from the order.
    $cc_data = $form_state->getValue('cc_data');

    if (strpos($cc_data['cc_number'], (string) $this->t('(Last 4) ')) === 0) {
      $cc_data['cc_number'] = $this->order->payment_details['cc_number'];
    }

    if (isset($cc_data['cc_cvv']) && isset($this->order->payment_details['cc_cvv'])) {
      if ($cc_data['cc_cvv'] == str_repeat('-', strlen($cc_data['cc_cvv']))) {
        $cc_data['cc_cvv'] = $this->order->payment_details['cc_cvv'];
      }
    }

    // Cache the values for use during processing.
    uc_credit_cache($cc_data, FALSE);

    // Build the data array passed on to the payment gateway.
    $txn_type = NULL;
    $reference = NULL;

    switch ($form_state->getValue('op')) {
      case $this->t('Charge amount'):
        $txn_type = UC_CREDIT_AUTH_CAPTURE;
        break;

      case $this->t('Authorize amount only'):
        $txn_type = UC_CREDIT_AUTH_ONLY;
        break;

      case $this->t('Set a reference only'):
        $txn_type = UC_CREDIT_REFERENCE_SET;
        break;

      case $this->t('Credit amount to this card'):
        $txn_type = UC_CREDIT_CREDIT;
        break;

      case $this->t('Capture amount to this authorization'):
        $txn_type = UC_CREDIT_PRIOR_AUTH_CAPTURE;
        $reference = $form_state->getValue('select_auth');
        break;

      case $this->t('Void authorization'):
        $txn_type = UC_CREDIT_VOID;
        $reference = $form_state->getValue('select_auth');
        break;

      case $this->t('Charge amount to this reference'):
        $txn_type = UC_CREDIT_REFERENCE_TXN;
        $reference = $form_state->getValue('select_ref');
        break;

      case $this->t('Remove reference'):
        $txn_type = UC_CREDIT_REFERENCE_REMOVE;
        $reference = $form_state->getValue('select_ref');
        break;

      case $this->t('Credit amount to this reference'):
        $txn_type = UC_CREDIT_REFERENCE_CREDIT;
        $reference = $form_state->getValue('select_ref');
    }

    $plugin = $this->paymentMethod->getPlugin();
    $result = $plugin->processPayment($this->order, $form_state->getValue('amount'), $txn_type, $reference);
    $this->order->payment_details = uc_credit_cache();
    $plugin->orderSave($this->order);

    if ($result) {
      $this->messenger()->addMessage($this->t('The credit card was processed successfully. See the admin comments for more details.'));
    }
    else {
      $this->messenger()->addError($this->t('There was an error processing the credit card. See the admin comments for details.'));
    }

    $form_state->setRedirect('entity.uc_order.canonical', ['uc_order' => $this->order->id()]);
  }

}
