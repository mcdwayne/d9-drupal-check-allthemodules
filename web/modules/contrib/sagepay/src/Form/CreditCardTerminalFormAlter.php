<?php

namespace Drupal\uc_sagepay\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\Entity\PaymentMethod;
use Drupal\uc_credit\Form\CreditCardTerminalForm;

/**
 * Displays the credit card terminal form for administrators.
 */
class CreditCardTerminalFormAlter extends CreditCardTerminalForm {

  /**
   * The order that is being processed.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $order;

  /**
   * The payment method that is in use.
   *
   * @var \Drupal\uc_payment\Entity\PaymentMethod
   */
  protected $paymentMethod;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_credit_terminal_sagepay_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(
    array $form,
    FormStateInterface $form_state,
    OrderInterface $uc_order = NULL,
    PaymentMethod $uc_payment_method = NULL) {

    $this->order = $uc_order;
    $this->paymentMethod = $uc_payment_method;

    $df = \Drupal::service('date.formatter');

    // Get the transaction types available to our default gateway.
    $types = $this->paymentMethod->getPlugin()->getTransactionTypes();

    $balance = uc_payment_balance($this->order);

    $form['order_total'] = [
      '#prefix' => '<div><strong>',
      '#markup' => $this->t(
        'Order total: @total',
        ['@total' => uc_currency_format($this->order->getTotal())]
      ),
      '#suffix' => '</div></strong>',
    ];

    $form['balance'] = [
      '#prefix' => '<div><strong>',
      '#markup' => $this->t(
        'Balance: @balance',
        ['@balance' => uc_currency_format($balance)]
      ),
      '#suffix' => '</div></strong>',
    ];

    // Let the administrator set the amount to charge.
    $form['amount'] = [
      '#type' => 'uc_price',
      '#title' => $this->t('Charge Amount'),
      '#default_value' => ($balance > 0) ?
      uc_currency_format($balance, FALSE, FALSE, '.') :
      0,
    ];

    // Build a credit card form.
    $form['specify_card'] = [
      '#type' => 'details',
      '#title' => $this->t('Credit card details'),
      '#description' => $this->t(
        'Use the available buttons in this fieldset to process with the
        specified card details.'
      ),
      '#open' => TRUE,
    ];
    $form['specify_card']['cc_data'] = [
      '#tree' => TRUE,
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>',
    ];

    $form['specify_card']['cc_data'] += $this->paymentMethod
      ->getPlugin()->cartDetails($this->order, [], $form_state);
    unset($form['specify_card']['cc_data']['cc_policy']);

    $form['specify_card']['actions'] = ['#type' => 'actions'];

    $form['specify_card']['actions']['defer_card'] = [
      '#type' => 'submit',
      '#value' => $this->t('Defer amount'),
    ];

    // If available, let the card be charged now.
    if (array_key_exists(UC_CREDIT_AUTH_CAPTURE, $types)) {
      $form['specify_card']['actions']['charge_card'] = [
        '#type' => 'submit',
        '#value' => $this->t('Charge amount'),
      ];
    }

    // If available, let the amount be authorized.
    if (array_key_exists(UC_CREDIT_AUTH_ONLY, $types)) {
      $form['specify_card']['actions']['authorize_card'] = [
        '#type' => 'submit',
        '#value' => $this->t('Authorize amount only'),
      ];
    }

    // If available, create a reference at the gateway.
    if (array_key_exists(UC_CREDIT_REFERENCE_SET, $types)) {
      $form['specify_card']['actions']['reference_set'] = [
        '#type' => 'submit',
        '#value' => $this->t('Set a reference only'),
      ];
    }

    // If available, create a reference at the gateway.
    if (array_key_exists(UC_CREDIT_CREDIT, $types)) {
      $form['specify_card']['actions']['credit_card'] = [
        '#type' => 'submit',
        '#value' => $this->t('Credit amount to this card'),
      ];
    }

    // Find any uncaptured authorizations.
    $options = [];

    if (isset($this->order->data->cc_txns['authorizations'])) {
      $cc_txns_auth = $this->order->data->cc_txns['authorizations'];

      foreach ($cc_txns_auth as $auth_id => $data) {
        if (empty($data['captured'])) {
          $options[$auth_id] = $this->t(
            '@auth_id - @date - @amount authorized',
            [
              '@auth_id' => strtoupper($auth_id),
              '@date' => $df->format($data['authorized'], 'short'),
              '@amount' => uc_currency_format($data['amount']),
            ]
          );
        }
      }
    }

    // If any authorizations existed...
    if (!empty($options)) {
      // Display a fieldset with the authorizations and available action
      // buttons.
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
      if (array_key_exists(UC_CREDIT_PRIOR_AUTH_CAPTURE, $types)) {
        $form['authorizations']['actions']['auth_capture'] = [
          '#type' => 'submit',
          '#value' => $this->t('Capture amount to this authorization'),
        ];
      }

      // If available, void a prior authorization.
      if (array_key_exists(UC_CREDIT_VOID, $types)) {
        $form['authorizations']['actions']['auth_void'] = [
          '#type' => 'submit',
          '#value' => $this->t('Void authorization'),
        ];
      }

      // Collapse this fieldset if no actions are available.
      if (!isset($form['authorizations']['actions']['auth_capture']) &&
          !isset($form['authorizations']['actions']['auth_void'])) {

        $form['authorizations']['#open'] = FALSE;
      }
    }

    // Find any uncaptured authorizations.
    $options = [];

    if (isset($this->order->data->cc_txns['references'])) {
      foreach ($this->order->data->cc_txns['references'] as $ref_id => $data) {
        $options[$ref_id] = $this->t(
          '@ref_id - @date - (Last 4) @card',
          [
            '@ref_id' => strtoupper($ref_id),
            '@date' => $df->format($data['created'], 'short'),
            '@card' => $data['card'],
          ]
        );
      }
    }

    // If any references existed...
    if (!empty($options)) {
      // Display a fieldset with the authorizations and available action
      // buttons.
      $form['references'] = [
        '#type' => 'details',
        '#title' => $this->t('Customer references'),
        '#description' => $this->t(
          'Use the available buttons in this fieldset to select and act on a
          customer reference.'
        ),
        '#open' => TRUE,
      ];

      $form['references']['select_ref'] = [
        '#type' => 'radios',
        '#title' => $this->t('Select references'),
        '#options' => $options,
      ];

      $form['references']['actions'] = ['#type' => 'actions'];

      // If available, capture a prior references.
      if (array_key_exists(UC_CREDIT_REFERENCE_TXN, $types)) {
        $form['references']['actions']['ref_capture'] = [
          '#type' => 'submit',
          '#value' => $this->t('Charge amount to this reference'),
        ];
      }

      // If available, remove a previously stored reference.
      if (array_key_exists(UC_CREDIT_REFERENCE_REMOVE, $types)) {
        $form['references']['actions']['ref_remove'] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove reference'),
        ];
      }

      // If available, remove a previously stored reference.
      if (array_key_exists(UC_CREDIT_REFERENCE_CREDIT, $types)) {
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

    // Find any deferred transactions.
    $options = [];
    if (isset($this->order->data->sagepay)) {
      foreach ($this->order->data->sagepay as $vendortxcode => $data) {
        if (isset($data['uc_sagepay_txtype']) && $data['uc_sagepay_txtype'] == 'DEFERRED' && empty($data['uc_sagepay_completed'])) {
          $options[$vendortxcode] = $this->t(
            '@vendortxcode - @date - @amount deferred',
            [
              '@vendortxcode' => strtoupper($vendortxcode),
              '@date' => $df->format($data['uc_sagepay_created'], 'short'),
              '@amount' => uc_currency_format($data['uc_sagepay_amount']),
            ]
          );
        }
      }
    }

    // If any authorizations existed...
    if (!empty($options)) {
      // Display a fieldset with the authorizations and available action
      // buttons.
      $form['sagepay_deferred'] = [
        '#type' => 'details',
        '#title' => $this->t('Deferred transactions'),
        '#description' => $this->t(
          'Use the available buttons in this fieldset to select and act on a
          prior DEFERRED transaction. The amount specified above will be
          RELEASED against the transaction selected below. Only one RELEASE is
          possible per transaction. You can RELEASE any amount up to the value
          of the original DEFERRED transaction.'
        ),
        '#open' => TRUE,
      ];

      $form['sagepay_deferred']['select_tx'] = [
        '#type' => 'radios',
        '#title' => $this->t('Select transaction'),
        '#options' => $options,
      ];

      // If available, capture a prior authorization.
      $form['sagepay_deferred']['actions']['uc_sagepay_release'] = [
        '#type' => 'submit',
        '#value' => $this->t('Release'),
      ];

      $form['sagepay_deferred']['actions']['uc_sagepay_abort'] = [
        '#type' => 'submit',
        '#value' => $this->t('Abort'),
      ];
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

    if (strpos($cc_data['cc_number'], (string) $this->t('(Last 4)') . ' ') === 0) {
      $cc_data['cc_number'] = $this->order->payment_details['cc_number'];
    }

    if (isset($cc_data['cc_cvv']) &&
      isset($this->order->payment_details['cc_cvv'])) {

      if ($cc_data['cc_cvv'] == str_repeat('-', strlen($cc_data['cc_cvv']))) {
        $cc_data['cc_cvv'] = $this->order->payment_details['cc_cvv'];
      }
    }

    // Cache the values for use during processing.
    uc_credit_cache($cc_data, FALSE);

    // Build the data array passed on to the payment gateway.
    $txn_type = NULL;
    $reference = NULL;

    switch ($form_state->getValue('op')->__toString()) {
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
        $reference['auth_id'] = $form_state->getValue('select_auth');
        break;

      case $this->t('Void authorization'):
        $txn_type = UC_CREDIT_VOID;
        $reference['auth_id'] = $form_state->getValue('select_auth');
        break;

      case $this->t('Charge amount to this reference'):
        $txn_type = UC_CREDIT_REFERENCE_TXN;
        $reference = $form_state->getValue('select_ref');
        break;

      case $this->t('Remove reference'):
        $txn_type = UC_CREDIT_REFERENCE_REMOVE;
        $reference = $form_state->getValue('select_ref');
        break;

      case $this->t('Defer amount'):
        $txn_type = 'sagepay_defer';
        break;

      case $this->t('Release'):
        $txn_type = [
          'txn_type' => 'sagepay_release',
          'ref_id' => $form_state->getValue('select_tx'),
        ];
        break;

      case $this->t('Abort'):
        $txn_type = [
          'txn_type' => 'sagepay_abort',
          'ref_id' => $form_state->getValue('select_tx'),
        ];
        break;

      case $this->t('Credit amount to this reference'):
        $txn_type = UC_CREDIT_REFERENCE_CREDIT;
        $reference = $form_state->getValue('select_ref');
        break;
    }

    $plugin = $this->paymentMethod->getPlugin();
    $result = $plugin->processPayment(
      $this->order,
      $form_state->getValue('amount'),
      $txn_type,
      $reference
    );
    $this->order->payment_details = uc_credit_cache();
    $plugin->orderSave($this->order);

    if ($result) {
      drupal_set_message($this->t(
        'The credit card was processed successfully. See the admin comments
        for more details.'
      ));
    }
    else {
      drupal_set_message($this->t(
        'There was an error processing the credit card. See the admin comments
        for details.'
        ),
        'error'
      );
    }

    $form_state->setRedirect(
      'entity.uc_order.canonical',
      ['uc_order' => $this->order->id()]
    );
  }

}
