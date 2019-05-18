<?php

namespace Drupal\uc_sagepay\Plugin\Ubercart\PaymentMethod;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_credit\CreditCardPaymentMethodBase;
use Drupal\uc_order\OrderInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Url;
use Drupal\uc_sagepay\SagePayActions as Actions;
use Drupal\user\Entity\User;

/**
 * Defines the SagePay payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "sagepay",
 *   name = @Translation("SagePay Gateway")
 * )
 */
class SagePay extends CreditCardPaymentMethodBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_sagepay_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = \Drupal::service('config.factory')
      ->getEditable('uc_sagepay.config');

    return parent::defaultConfiguration() + [
      'txn_type' => UC_CREDIT_AUTH_ONLY,
      'uc_sagepay_vendortxcode_format' => $config->get(
        'uc_sagepay_vendortxcode_format'
      ),
      'uc_sagepay_vendor' => $config->get('uc_sagepay_vendor'),
      'uc_sagepay_server' => $config->get('uc_sagepay_server'),
      'uc_sagepay_iframe' => $config->get('uc_sagepay_iframe'),
    ];

  }

  /**
   * Returns the set of fields which are used by this payment method.
   *
   * @return array
   *   An array with keys 'cvv', 'owner', 'start', 'issue', 'bank' and 'type'.
   */
  public function getEnabledFields() {
    return [
      'cvv' => TRUE,
      'owner' => TRUE,
      'start' => FALSE,
      'issue' => FALSE,
      'bank' => FALSE,
      'type' => TRUE,
    ];
  }

  /**
   * Returns the set of card types which are used by this payment method.
   *
   * @return array
   *   An array with keys as needed by the chargeCard() method and values
   *   that can be displayed to the customer.
   */
  public function getEnabledTypes() {
    return [
      'visa' => $this->t('Visa'),
      'mastercard' => $this->t('MasterCard'),
      'discover' => $this->t('Discover'),
      'amex' => $this->t('American Express'),
    ];
  }

  /**
   * Returns the set of transaction types allowed by this payment method.
   *
   * @return array
   *   An array with values UC_CREDIT_AUTH_ONLY, UC_CREDIT_PRIOR_AUTH_CAPTURE,
   *   UC_CREDIT_AUTH_CAPTURE, UC_CREDIT_REFERENCE_SET, UC_CREDIT_REFERENCE_TXN,
   *   UC_CREDIT_REFERENCE_REMOVE, UC_CREDIT_REFERENCE_CREDIT, UC_CREDIT_CREDIT
   *   and UC_CREDIT_VOID.
   */
  public function getTransactionTypes() {
    return [
      UC_CREDIT_AUTH_CAPTURE => $this->t('Authorize and capture immediately'),
      UC_CREDIT_AUTH_ONLY => $this->t('Authorization only (AUTHENTICATE)'),
      UC_CREDIT_PRIOR_AUTH_CAPTURE => $this->t(
        'Capture amount to this authorization'
      ),
      UC_CREDIT_REFERENCE_CREDIT => $this->t('Credit amount to this reference'),
      'sagepay_defer' => $this->t('Deferred transaction'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function cartDetails(
    OrderInterface $order,
    array $form,
    FormStateInterface $form_state) {

    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => 'uc-credit-form'],
    ];
    $build['#attached']['library'][] = 'uc_credit/uc_credit.styles';
    $build['cc_policy'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t(
        'Your billing information must match the billing address for the credit
        card entered below or we will be unable to process your payment.'
      ),
      '#suffix' => '</p>',
    ];

    // Encrypted data in the session is from the user returning from the review
    // page.
    $session = \Drupal::service('session');
    if ($session->has('sescrd')) {

      $order->payment_details = uc_credit_cache($session->get('sescrd'));
      $build['payment_details_data'] = [
        '#type' => 'hidden',
        '#value' => base64_encode($session->get('sescrd')),
      ];
      $session->remove('sescrd');
    }
    elseif (isset($_POST['panes']['payment']['details']['payment_details_data'])) {
      // Copy any encrypted data that was POSTed in.
      $build['payment_details_data'] = [
        '#type' => 'hidden',
        '#value' => $_POST['panes']['payment']['details']['payment_details_data'],
      ];
    }

    $fields = $this->getEnabledFields();
    if (!empty($fields['type'])) {
      $build['cc_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Card type'),
        '#options' => $this->getEnabledTypes(),
        '#default_value' => isset($order->payment_details['cc_type']) ?
        $order->payment_details['cc_type'] :
        NULL,
      ];
    }

    if (!empty($fields['owner'])) {
      $build['cc_owner'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Card owner'),
        '#default_value' => isset($order->payment_details['cc_owner']) ?
        $order->payment_details['cc_owner'] :
        '',
        '#attributes' => ['autocomplete' => 'off'],
        '#size' => 32,
        '#maxlength' => 64,
        '#required' => TRUE,
      ];
    }

    // Set up the default CC number on the credit card form.
    if (!isset($order->payment_details['cc_number'])) {
      $default_num = NULL;
    }
    elseif (!$this->validateCardNumber($order->payment_details['cc_number'])) {
      // Display the number as is if it does not validate so it can be
      // corrected.
      $default_num = $order->payment_details['cc_number'];
    }
    else {
      // Otherwise default to the last 4 digits.
      $default_num = $this->t('(Last 4)');
      $default_num .= ' ' . substr($order->payment_details['cc_number'], -4);
    }

    $build['cc_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card number'),
      '#default_value' => $default_num,
      '#attributes' => ['autocomplete' => 'off'],
      '#size' => 20,
      '#maxlength' => 19,
      '#required' => TRUE,
    ];

    if (!empty($fields['start'])) {
      $month = isset($order->payment_details['cc_start_month']) ?
      $order->payment_details['cc_start_month'] :
      NULL;

      $year = isset($order->payment_details['cc_start_year']) ?
      $order->payment_details['cc_start_year'] :
      NULL;

      $year_range = range(date('Y') - 10, date('Y'));
      $build['cc_start_month'] = [
        '#type' => 'number',
        '#title' => $this->t('Start date'),
        '#options' => [
          1 => $this->t('01 - January'),
          2 => $this->t('02 - February'),
          3 => $this->t('03 - March'),
          4 => $this->t('04 - April'),
          5 => $this->t('05 - May'),
          6 => $this->t('06 - June'),
          7 => $this->t('07 - July'),
          8 => $this->t('08 - August'),
          9 => $this->t('09 - September'),
          10 => $this->t('10 - October'),
          11 => $this->t('11 - November'),
          12 => $this->t('12 - December'),
        ],
        '#default_value' => $month,
        '#required' => TRUE,
      ];

      $build['cc_start_year'] = [
        '#type' => 'select',
        '#title' => $this->t('Start year'),
        '#title_display' => 'invisible',
        '#options' => array_combine($year_range, $year_range),
        '#default_value' => $year,
        '#field_suffix' => $this->t('(if present)'),
        '#required' => TRUE,
      ];
    }

    $month = isset($order->payment_details['cc_exp_month']) ?
    $order->payment_details['cc_exp_month'] :
    1;

    $year = isset($order->payment_details['cc_exp_year']) ?
    $order->payment_details['cc_exp_year'] :
    date('Y');

    $year_range = range(date('Y'), date('Y') + 20);
    $build['cc_exp_month'] = [
      '#type' => 'select',
      '#title' => $this->t('Expiration date'),
      '#options' => [
        1 => $this->t('01 - January'),
        2 => $this->t('02 - February'),
        3 => $this->t('03 - March'),
        4 => $this->t('04 - April'),
        5 => $this->t('05 - May'),
        6 => $this->t('06 - June'),
        7 => $this->t('07 - July'),
        8 => $this->t('08 - August'),
        9 => $this->t('09 - September'),
        10 => $this->t('10 - October'),
        11 => $this->t('11 - November'),
        12 => $this->t('12 - December'),
      ],
      '#default_value' => $month,
      '#required' => TRUE,
    ];

    $build['cc_exp_year'] = [
      '#type' => 'select',
      '#title' => $this->t('Expiration year'),
      '#title_display' => 'invisible',
      '#options' => array_combine($year_range, $year_range),
      '#default_value' => $year,
      '#field_suffix' => $this->t('(if present)'),
      '#required' => TRUE,
    ];

    if (!empty($fields['issue'])) {
      // Set up the default Issue Number on the credit card form.
      if (empty($order->payment_details['cc_issue'])) {
        $default_card_issue = NULL;
      }
      elseif (!$this->validateIssueNumber($order->payment_details['cc_issue'])) {
        // Display the Issue Number as is if it does not validate so it can be
        // corrected.
        $default_card_issue = $order->payment_details['cc_issue'];
      }
      else {
        // Otherwise mask it with dashes.
        $default_card_issue = str_repeat(
          '-',
          strlen($order->payment_details['cc_issue'])
        );
      }

      $build['cc_issue'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Issue number'),
        '#default_value' => $default_card_issue,
        '#attributes' => ['autocomplete' => 'off'],
        '#size' => 2,
        '#maxlength' => 2,
        '#field_suffix' => $this->t('(if present)'),
      ];
    }

    if (!empty($fields['cvv'])) {
      // Set up the default CVV on the credit card form.
      if (empty($order->payment_details['cc_cvv'])) {
        $default_cvv = NULL;
      }
      elseif (!$this->validateCvv($order->payment_details['cc_cvv'])) {
        // Display the CVV as is if it does not validate so it can be corrected.
        $default_cvv = $order->payment_details['cc_cvv'];
      }
      else {
        // Otherwise mask it with dashes.
        $default_cvv = str_repeat(
          '-',
          strlen($order->payment_details['cc_cvv'])
        );
      }

      $build['cc_cvv'] = [
        '#type' => 'textfield',
        '#title' => $this->t('CVV'),
        '#default_value' => $default_cvv,
        '#attributes' => ['autocomplete' => 'off'],
        '#size' => 4,
        '#required' => TRUE,
        '#maxlength' => 4,
        '#field_suffix' => [
          '#theme' => 'uc_credit_cvv_help',
          '#method' => $order->getPaymentMethodId(),
        ],
      ];
    }

    if (!empty($fields['bank'])) {
      $build['cc_bank'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Issuing bank'),
        '#default_value' => isset($order->payment_details['cc_bank']) ?
        $order->payment_details['cc_bank'] :
        '',
        '#attributes' => ['autocomplete' => 'off'],
        '#size' => 32,
        '#maxlength' => 64,
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(
    array $form,
    FormStateInterface $form_state) {

    $form['logo'] = [
      '#markup' =>
      '<img src="/modules/custom/uc_sagepay/img/sagepay-logo.gif"/>',
    ];

    $form['uc_sagepay_vendor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sage Pay vendor name'),
      '#default_value' => $this->configuration['uc_sagepay_vendor'] ?
      $this->configuration['uc_sagepay_vendor'] :
      '',
      '#size' => 15,
      '#maxlength' => 15,
    ];

    $form['uc_sagepay_server'] = [
      '#type' => 'radios',
      '#title' => $this->t('Sage Pay server'),
      '#options' => [
        'showpost' => $this->t(
          'Showpost (only use when requested by Sage Pay support)'
        ),
        'test' => $this->t('Test Server'),
        'live' => $this->t('Live System'),
      ],
      '#default_value' => $this->configuration['uc_sagepay_server'] ?
      $this->configuration['uc_sagepay_server'] :
      'test',
    ];

    $form['uc_sagepay_iframe'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use inline frames for 3D-Secure'),
      '#default_value' => $this->configuration['uc_sagepay_iframe'] ?
      $this->configuration['uc_sagepay_iframe'] :
      0,
      '#description' => $this->t(
        '<strong>For 3D-Secure transactions only.</strong>  Using an inline
        frame allows the transaction to appear as if it\'s all taking place at
        this store, but it\'s not W3C standards compliant if you\'re using the
        normal (for Drupal) XHTML Strict Document Type Declaration.  However,
        this is not really a problem in practice, or you can change your
        theme\'s DTD (e.g., to a Frameset DTD) if you prefer.  If this setting
        is turned off, it will be obvious to the user that he is being
        redirected to a different server to complete the transaction, rather
        like Sage Pay Form.'
      ),
    ];

    $form['uc_sagepay_vendortxcode_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('VendorTXCode format'),
      '#default_value' =>
      $this->configuration['uc_sagepay_vendortxcode_format'] ?
      $this->configuration['uc_sagepay_vendortxcode_format'] :
      '',
      '#description' => $this->t(
        'The VendorTXCode is the unique identifier for a Sage Pay transaction.
        This field specifies the format of the VendorTXCode using tokens.
        Multiple transactions may be performed against an order (for example,
        when an initial attempt at payment fails) so you should include one of
        the random number tokens to ensure that each generated code is unique.
        The maximum length of a formatted VendorTXCode is 40 characters.'
      ),
      '#required' => TRUE,
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['site', 'uc_order', 'store'],
        '#dialog' => TRUE,
        '#click_insert' => TRUE,
        '#show_restricted' => TRUE,
      ];

      $form['sagepay-tokens'] = [
        '#markup' => '<p>Tokens can be used. </p>',
      ];
    }

    $form['txn_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Transaction type'),
      '#default_value' => $this->configuration['txn_type'],
      '#options' => $this->getTransactionTypes(),
    ];

    $img_path = base_path() . drupal_get_path('module', 'uc_sagepay') . '/img';
    $img_paths = base_path() . drupal_get_path('module', 'uc_sagepay');
    $img_paths .= '/img/protx_cards';

    $output = '
    <div class="uc-sagepay-cards">
      <img src="' . $img_paths . '/mastercard normal.gif" alt="Mastercard"/>
      <img src="' . $img_paths . '/visa.gif" alt="Visa" />
      <img src="' . $img_paths . '/delta.gif" alt="Visa Debit" />
      <img src="' . $img_paths . '/electron.gif" alt="Electron" />
      <img src="' . $img_paths . '/amexsmall.gif" alt="American Express" />
    </div>
    <div class="uc-sagepay-cards">
      <img src="' . $img_paths . '/maestro.gif" alt="Maestro" />
      <img src="' . $img_paths . '/solo.gif" alt="Solo" />
      <img src="' . $img_paths . '/dinersclublogo125_26.gif" alt="Diners" />
      <img src="' . $img_paths . '/jcb.gif" alt="JCB" />
    </div>
    <div class="uc-sagepay-cards">
      <p>This gateway supports 3D-Secure:</p>
      <img src="' . $img_path . '/vbv_logo24.gif" alt="Verified by Visa" />
      <img src="' . $img_path . '/msc_logo24.gif" alt="Mastercard SecureCode" />
    </div>';

    $form['cards'] = [
      '#markup' => $output,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $vars = $form_state->getValue('settings');
    $this->configuration['uc_sagepay_vendor'] = $vars['uc_sagepay_vendor'];
    $this->configuration['uc_sagepay_server'] = $vars['uc_sagepay_server'];
    $this->configuration['uc_sagepay_iframe'] = $vars['uc_sagepay_iframe'];
    $this->configuration['txn_type'] = $form_state->getValue('txn_type');
    $this->configuration['uc_sagepay_vendortxcode_format'] =
    $vars['uc_sagepay_vendortxcode_format'];

    $config = \Drupal::service('config.factory')
      ->getEditable('uc_sagepay.config');

    $config->set('uc_sagepay_vendor', $vars['uc_sagepay_vendor'])
      ->set('uc_sagepay_iframe', $vars['uc_sagepay_iframe'])
      ->save();

    $configuration = [
      'uc_sagepay_vendor' => $form_state->getValue('uc_sagepay_vendor'),
      'uc_sagepay_server' => $form_state->getValue('uc_sagepay_server'),
      'uc_sagepay_iframe' => $form_state->getValue('uc_sagepay_iframe'),
      'uc_sagepay_vendortxcode_format' => $form_state->getValue(
        'uc_sagepay_vendortxcode_format'
      ),
    ];

    parent::setConfiguration($configuration);
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Called when a credit card should be processed.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being processed. Credit card details supplied by the
   *   user are available in $order->payment_details[].
   * @param float $amount
   *   The amount that should be charged.
   * @param string $txn_type
   *   The transaction type, one of the UC_CREDIT_* constants.
   * @param string $reference
   *   (optional) The payment reference, where needed for specific transaction
   *   types.
   *
   * @return array
   *   Returns an associative array with the following members:
   *   - "success": TRUE if the transaction succeeded, FALSE otherwise.
   *   - "message": a human-readable message describing the result of the
   *     transaction.
   *   - "log_payment": TRUE if the transaction should be regarded as a
   *     successful payment.
   *   - "uid": The user ID of the person logging the payment, or 0 if the
   *     payment was processed automatically.
   *   - "comment": The comment string, markup allowed, to enter in the
   *     payment log.
   *   - "data": Any data that should be serialized and stored with the payment.
   */
  protected function chargeCard(OrderInterface $order, $amount, $txn_type, $reference = NULL) {
    $user = \Drupal::currentUser();

    // Unwrap array if deferred transactions.
    $ref_id = FALSE;
    if (is_array($txn_type)) {
      $txn_type = $txn_type['txn_type'];
      $ref_id = $txn_type['ref_id'];
    }

    // Get config items.
    $config = \Drupal::service('config.factory')
      ->getEditable('uc_sagepay.config');

    // Format a new VendorTXCode using tokens.
    $vendortxcode = $this->configuration['uc_sagepay_vendortxcode_format'];
    $vendortxcode = \Drupal::token()->replace(
      $vendortxcode, ['uc_order' => $order]
    );
    $vendortxcode = substr($vendortxcode, 0, 40);

    // Fields required for SagePay transaction.
    $referrer = 'uc_sagepay-8.x';

    $transaction = [
      'VPSProtocol' => '3.00',
      'Vendor' => $this->configuration['uc_sagepay_vendor'],
      'VendorTxCode' => $vendortxcode,
      'Amount' => number_format($amount, 2, '.', ''),
      'Currency' => $order->getCurrency(),
      'ReferrerID' => $referrer,
    ];

    // Add fields specific to the transaction type.
    switch ($txn_type) {

      // Release deferred transactions.
      case 'sagepay_abort':
      case 'sagepay_release':
        $vendortxcode = $ref_id;
        $transaction = array_merge(
          $transaction,
          [
            'VendorTxCode' => $vendortxcode,
            'VPSTxId' => $order->data->sagepay[$vendortxcode]['VPSTxId'],
            'SecurityKey' => $order->data->sagepay[$vendortxcode]['SecurityKey'],
            'TxAuthNo' => $order->data->sagepay[$vendortxcode]['TxAuthNo'],
            'ReleaseAmount' => $transaction['Amount'],
          ]
        );

        if ($txn_type == 'sagepay_release') {
          $transaction = array_merge(
            $transaction,
            [
              'TxType' => 'RELEASE',
              'Description' => $this->t(
                'Releasing order @orderid.',
                ['@orderid' => $order->id()]
              ),
            ]
          );
        }
        if ($txn_type == 'sagepay_abort') {
          $transaction = array_merge(
            $transaction,
            [
              'TxType' => 'ABORT',
              'Description' => $this->t(
                'Aborting order @orderid.',
                ['@orderid' => $order->id()]
              ),
            ]
          );
        }
        break;

      case UC_CREDIT_PRIOR_AUTH_CAPTURE:
        // Capture a prior authorisation.
        $description = $this->t(
          'Authorising order @orderid.',
          ['@orderid' => $order->id()]
        );

        $transaction = array_merge(
          $transaction,
          [
            'TxType' => 'AUTHORISE',
            'Description' => $description->__toString(),
            'RelatedVPSTxId' =>
            $order->data->sagepay[$reference['auth_id']]['VPSTxId'],
            'RelatedVendorTxCode' => $reference['auth_id'],
            'RelatedSecurityKey' =>
            $order->data->sagepay[$reference['auth_id']]['SecurityKey'],
            'ApplyAVSCV2' => '0',
          ]
        );
        break;

      case UC_CREDIT_REFERENCE_CREDIT:
        // Refund a previous transaction.
        $vendortxcode = $ref_id;
        $description = $this->t(
          'Refund order @orderid.',
          ['@orderid' => $order->id()]
        );
        $transaction = array_merge(
          $transaction,
          [
            'TxType' => 'REFUND',
            'Description' => $description->__toString(),
            'RelatedVPSTxId' => $order->data->sagepay[$vendortxcode]['VPSTxId'],
            'RelatedVendorTxCode' => $vendortxcode,
            'RelatedSecurityKey' =>
            $order->data->sagepay[$vendortxcode]['SecurityKey'],
            'RelatedTxAuthNo' =>
            $order->data->sagepay[$vendortxcode]['TxAuthNo'],
          ]
        );
        break;

      case 'sagepay_defer':
      case UC_CREDIT_AUTH_CAPTURE:
      case UC_CREDIT_AUTH_ONLY:
        if ($txn_type == UC_CREDIT_AUTH_CAPTURE) {
          $transaction['TxType'] = 'PAYMENT';
        }
        if ($txn_type == UC_CREDIT_AUTH_ONLY) {
          $transaction['TxType'] = 'AUTHENTICATE';
        }
        if ($txn_type == 'sagepay_defer') {
          $transaction['TxType'] = 'DEFERRED';
        }
        break;

      // Fall through to default case.
      default:
        // Sanity check credit card number, in case validation is disabled.
        if (isset($order->payment_details['cc_number']) &&
            !ctype_digit($order->payment_details['cc_number'])) {

          return [
            'success' => FALSE,
            'message' => t('You have entered an invalid credit card number.'),
          ];
        }

        // CardHolder must be supplied, but use the billing name if the separate
        // field is not available.
        if (!empty($order->payment_details['cc_owner'])) {
          $cardholder = $order->payment_details['cc_owner'];
        }
        else {
          $cardholder = $order->billing_first_name . ' ';
          $cardholder .= $order->billing_last_name;
        }

        $billing_address = $order->getAddress('billing');
        $billing_street = $billing_address->getStreet1();
        if ($billing_address->getStreet2()) {
          $billing_street .= ', ' . $billing_address->getStreet2();
        }

        $transaction = array_merge($transaction, [
          'Description' => Actions::sagepayDescription($order),
          'CardHolder' => substr($cardholder, 0, 50),
          'CardNumber' => $order->payment_details['cc_number'],
          'ExpiryDate' => sprintf(
            '%02d',
            $order->payment_details['cc_exp_month']
          ) . substr($order->payment_details['cc_exp_year'], -2),
          'CV2' => $order->payment_details['cc_cvv'],
          'CardType' => Actions::parseCardType(
            $order->payment_details['cc_type']
          ),
          'BillingSurname' => substr($billing_address->getLastName(), 0, 50),
          'BillingFirstnames' => substr(
            $billing_address->getFirstName(),
            0,
            50
          ),
          'BillingAddress1' => substr($billing_address->getStreet1(), 0, 100),
          'BillingAddress2' => $billing_address->getStreet2() ?
          substr($billing_address->getStreet2(), 0, 100) :
          '',
          'BillingCity' => substr($billing_address->getCity(), 0, 40),
          'BillingPostCode' => substr($billing_address->getPostalCode(), 0, 20),
          'BillingCountry' => $billing_address->getCountry(),
          'BillingPhone' => substr(
            trim(
              preg_replace('/[^-\d+() ]/',
                '',
                $billing_address->getPhone()
              )
            ),
            0,
            20
          ),
          'DeliverySurname' => substr($billing_address->getLastName(), 0, 20),
          'DeliveryFirstnames' => substr(
            $billing_address->getFirstName(),
            0,
            20
          ),
          'DeliveryAddress1' => substr($billing_address->getStreet1(), 0, 100),
          'DeliveryAddress2' => $billing_address->getStreet2() ?
          substr($billing_address->getStreet2(), 0, 100) :
          '',
          'DeliveryCity' => substr($billing_address->getCity(), 0, 40),
          'DeliveryPostCode' => substr(
            $billing_address->getPostalCode(),
            0,
            10
          ),
          'DeliveryCountry' => $billing_address->getCountry(),
          'DeliveryPhone' => substr(
            trim(
              preg_replace('/[^-\d+() ]/',
                '',
                $billing_address->getPhone()
              )
            ),
            0,
            20
          ),
          'Basket' => Actions::sagepayBasket($order, $amount),
          'GiftAidPayment' => '0',
          'ApplyAVSCV2' => '0',
          'ClientIPAddress' => \Drupal::request()->getClientIp(),
          'Apply3DSecure' => $config->get('uc_sagepay_iframe'),
          'AccountType' => 'E',
        ]);

        // Only send email addresses that will validate with Sage Pay.
        if (Actions::sagepayValidEmail($order->primary_email->value)) {
          $transaction['CustomerEmail'] = $order->primary_email->value;
        }

        // If the order is not shippable or the delivery checkout pane is
        // disabled, send billing address as delivery address.
        if (!$order->isShippable()) {
          foreach ($transaction as $key => $value) {
            if (substr($key, 0, 7) == 'Billing') {
              $transaction['Delivery' . substr($key, 7)] = $value;
            }
          }
        }

        // Send credit card start date if available.
        if (!empty($order->payment_details['cc_start_month']) &&
          !empty($order->payment_details['cc_start_year'])) {
          $transaction['StartDate'] = sprintf(
            '%02d',
            $order->payment_details['cc_start_month']
          ) . substr(
            $order->payment_details['cc_start_year'],
            -2
          );
        }

        // Send issue number for Maestro and Solo cards, if available.
        if (($transaction['CardType'] == 'MAESTRO' ||
          $transaction['CardType'] == 'SOLO') &&
          !empty($order->payment_details['cc_issue'])) {

          $transaction['IssueNumber'] = $order->payment_details['cc_issue'];
        }

        // Don't process the transaction if another module reported a problem.
        if (isset($transaction['Error'])) {
          uc_order_comment_save(
            $order->id(),
            $user->id(),
            $transaction['Error']
          );

          return [
            'success' => FALSE,
            'uid' => $user->id(),
            'message' => $transaction['Error'],
          ];
        }
        break;
    }

    // Send transaction to the Sage Pay server.
    uc_order_comment_save(
      $order->id(),
      $user->id(),
      t(
        'Transaction sent: @TxType.<br />VendorTxCode: @VendorTxCode<br />
        Amount: @Amount<br />Currency: @Currency',
        [
          '@VendorTxCode' => $transaction['VendorTxCode'],
          '@TxType' => $transaction['TxType'],
          '@Amount' => $transaction['Amount'],
          '@Currency' => $transaction['Currency'],
        ]
      )
    );

    return $this->ucSagepayTransaction(
      $transaction['TxType'],
      $transaction,
      $order,
      $vendortxcode,
      $amount
    );
  }

  /**
   * Perform a Sage Pay transaction.
   */
  public function ucSagepayTransaction(
    $method,
    $transaction,
    $order = NULL,
    $vendortxcode = NULL,
    $amount = NULL) {

    $user = User::load(\Drupal::currentUser()->id());
    // Send the transaction data to Sage Pay.
    $url = $this->sagepayUrl($method);

    $client = new Client();
    $request = new Request(
      'POST',
      $url,
      [
        'Content-Type' => 'application/x-www-form-urlencoded;',
      ],
      http_build_query($transaction, '', '&')
    );

    $response = $client->send($request, []);

    $log = explode('|', $response->getBody(TRUE));

    \Drupal::logger('uc_sagepay')->notice(
      'Debug response: @data',
      ['@data' => '<pre>' . print_r($log, TRUE) . '</pre>']
    );

    $result = [
      'success' => TRUE,
      'uid' => $order->getOwnerId(),
    ];

    // Exit immediately if the request failed.
    if ($response->getStatusCode() != '200') {
      $result['message'] = t(
        'Sage Pay HTTP request failed: %error.',
        [
          '%error' => $response->getStatusCode() . ' ' . $response->getBody(),
        ]
      );

      if ($order) {
        uc_order_comment_save(
          $order->id(),
          $user->get('uid')->value,
          $result['message']
        );
      }

      return $result;
    }

    // Showpost mode returns no response, Sage Pay support must investigate
    // further.
    if ($this->configuration['uc_sagepay_server'] == 'showpost') {
      $result['message'] = t(
        'Sage Pay showpost complete. Please contact Sage Pay with your vendor
        ID and the time and date of this transaction for more information.'
      );

      drupal_set_message($result['message']);

      return $result;
    }

    // Parse response string.
    $data = $comments = [];

    foreach (explode("\r\n", trim($response->getBody())) as $str) {
      list($key, $value) = explode('=', $str, 2);

      $data[$key] = $value;
      if ($key != 'VPSProtocol') {
        $comments[] = "$key: $value";
      }
    }

    switch ($data['Status']) {
      // @TODO add 3DAUTH.
      case 'OK':
        $result['success'] = TRUE;
        // @TODO add 3DAUTH.
        switch ($method) {
          case 'AUTHORISE':
            // Log capture of prior authorisation.
            uc_credit_log_prior_auth_capture(
              $order->id(),
              $transaction['RelatedVendorTxCode']
            );
            array_unshift($comments, t('Transaction authorized.'));

            // Log transaction reference for capture so it can be refunded
            // later.
            $order->data = uc_credit_log_reference(
              $order->id(),
              $vendortxcode,
              $order->payment_details['cc_number']
            );
            $order->data = $this->logTxData($order->id(), $vendortxcode, $data);
            break;

          case 'DEFERRED':
            $result['log_payment'] = FALSE;
            array_unshift($comments, t('Transaction deferred.'));
            // Store custom time to follow same approach as built-in credit
            // logs.
            $data['uc_sagepay_txtype'] = $method;
            $data['uc_sagepay_created'] = REQUEST_TIME;
            $data['uc_sagepay_amount'] = $amount;
            $order->data = $this->logTxData($order->id(), $vendortxcode, $data);
            break;

          case 'RELEASE':
            // Log transaction reference for capture so it can be refunded
            // later.
            $order->data = uc_credit_log_reference(
              $order->id(),
              $vendortxcode,
              $order->payment_details['cc_number']
            );

            array_unshift($comments, t('Transaction released.'));

            $data['uc_sagepay_completed'] = REQUEST_TIME;
            $order->data = $this->logTxData($order->id(), $vendortxcode, $data);
            break;

          case 'PAYMENT':
            // Log transaction reference for capture so it can be refunded
            // later.
            $order->data = uc_credit_log_reference(
              $order->id(),
              $vendortxcode,
              $order->payment_details['cc_number']
            );

            array_unshift($comments, t('Transaction completed.'));
            $order->data = $this->logTxData($order->id(), $vendortxcode, $data);
            break;

          case 'ABORT':
            $result['log_payment'] = FALSE;
            array_unshift($comments, t('Transaction aborted.'));
            // Store custom time to follow same approach as built-in credit
            // logs.
            $data['uc_sagepay_completed'] = REQUEST_TIME;
            $order->data = $this->logTxData($order->id(), $vendortxcode, $data);
            break;

          case 'REFUND':
            // Log refund as a negative payment.
            array_unshift($comments, t('Transaction refunded.'));

            uc_payment_enter(
              $order->id(),
              'credit',
              -$amount,
              $user->id(),
              '',
              implode($comments, '<br />')
            );

            $result['log_payment'] = FALSE;
            break;

          default:
            // Another module will be processing this transaction and will need
            // the result data.
            array_unshift($comments, t('Transaction succeeded.'));
            $result['data'] = $data;
        }
        break;

      case 'REGISTERED':
      case 'AUTHENTICATED':
        // Log an authorisation.
        $order->data = uc_sagepay_log_authorization(
          $order->id(),
          $vendortxcode,
          $amount
        );

        $order->data = $this->logTxData(
          $order->id(),
          $vendortxcode,
          $data,
          TRUE
        );

        array_unshift($comments, t('Transaction authenticated.'));
        $result['success'] = TRUE;
        $result['log_payment'] = FALSE;
        break;

      case 'REJECTED':
        $result['message'] = $this->t(
          'The VSP System rejected the transaction because of the rules you
          have set on your Sage Pay account.  The message was: %StatusDetail',
          ['%StatusDetail' => $data['StatusDetail']]
        );
        break;

      case 'NOTAUTHED':
      case 'INVALID':
      case 'MALFORMED':
      case 'ERROR':
        if (Actions::parseError($data['StatusDetail'])) {
          $result['message'] = $data['StatusDetail'];
        }

        $result['success'] = FALSE;
        break;

      default:
        break;
    }

    $result['comment'] = implode($comments, '<br />');
    if ($order) {
      uc_order_comment_save(
        $order->id(),
        $user->get('uid')->value,
        $result['comment']
      );
    }

    return $result;
  }

  /**
   * Process a payment through the credit card gateway.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order that is being processed.
   * @param float $amount
   *   The amount of the payment we're attempting to collect.
   * @param string $txn_type
   *   The transaction type, one of the UC_CREDIT_* constants.
   * @param string $reference
   *   (optional) The payment reference, where needed for specific transaction
   *   types.
   *
   * @return bool
   *   TRUE or FALSE indicating whether or not the payment was processed.
   */
  public function processPayment(
    OrderInterface $order,
    $amount,
    $txn_type,
    $reference = NULL) {

    // Ensure the cached details are loaded.
    $this->orderLoad($order);

    $result = $this->chargeCard($order, $amount, $txn_type, $reference);

    // If the payment processed successfully...
    if ($result['success']) {
      // Log the payment to the order if not disabled.
      uc_payment_enter(
        $order->id(),
        $this->getPluginDefinition()['id'],
        uc_currency_format($amount),
        empty($result['uid']) ? 0 : $result['uid'],
        empty($result['data']) ? '' : $result['data'],
        empty($result['comment']) ? '' : $result['comment']
      );

      $order->setStatusId('completed')->save();
    }
    else {
      if (empty($result['message'])) {
        $result['message'] = [];
      }
      else {
        $msg = explode(':', $result['message']);
        $msg_formatted = end($msg);

        drupal_set_message($msg_formatted, 'error');
      }
      // Otherwise display the failure message in the logs.
      \Drupal::logger('uc_payment')->warning(
        'Payment failed for order @order_id: @message',
        [
          '@order_id' => $order->id(),
          '@message' => $result['message'],
          'link' => $order->toLink($this->t('view order'))->toString(),
        ]
      );
    }

    return $result['success'];
  }

  /**
   * {@inheritdoc}
   */
  public function orderSave(OrderInterface $order) {
    // Save only some limited, PCI compliant data.
    $cc_data = $order->payment_details;

    // Stuff the serialized and encrypted CC details into the array.
    $crypt = \Drupal::service('uc_store.encryption');
    $order->data->cc_data = $crypt->encrypt(
      uc_credit_encryption_key(),
      base64_encode(serialize($cc_data))
    );
    uc_store_encryption_errors($crypt, 'uc_credit');
  }

  /**
   * Return a Sage Pay transaction URL for the given transaction type.
   *
   * It uses the current server selection.
   *
   * @param string $method
   *   The method string.
   *
   * @return array
   *   The updated data array.
   */
  public function sagepayUrl($method) {
    $server = $this->configuration['uc_sagepay_server'];

    // Showpost debugging always uses the same URL.
    if ($server == 'showpost') {
      return 'https://test.sagepay.com/showpost/showpost.asp';
    }

    // Simulator doesn't support version 3.0 as of Sep 2014.
    $servers = [
      'PAYMENT' => [
        'test' => 'https://test.sagepay.com/gateway/service/vspdirect-register.vsp',
        'live' => 'https://live.sagepay.com/gateway/service/vspdirect-register.vsp',
      ],
      'AUTHORISE' => [
        'test' => 'https://test.sagepay.com/gateway/service/authorise.vsp',
        'live' => 'https://live.sagepay.com/gateway/service/authorise.vsp',
      ],
      'REFUND' => [
        'test' => 'https://test.sagepay.com/gateway/service/refund.vsp',
        'live' => 'https://live.sagepay.com/gateway/service/refund.vsp',
      ],
      'RELEASE' => [
        'test' => 'https://test.sagepay.com/gateway/service/release.vsp',
        'live' => 'https://live.sagepay.com/gateway/service/release.vsp',
      ],
      'ABORT' => [
        'test' => 'https://test.sagepay.com/gateway/service/abort.vsp',
        'live' => 'https://live.sagepay.com/gateway/service/abort.vsp',
      ],
      '3D-SECURE' => [
        'test' => 'https://test.sagepay.com/gateway/service/direct3dcallback.vsp',
        'live' => 'https://live.sagepay.com/gateway/service/direct3dcallback.vsp',
      ],
    ];

    $servers['DEFERRED'] = $servers['AUTHENTICATE'] = $servers['PAYMENT'];

    \Drupal::moduleHandler()->alter('uc_sagepay_url', $servers);

    return $servers[$method][$server];
  }

  /**
   * Logs tx data to an order's data array.
   *
   * @param string $order_id
   *   The order associated with the credit card details.
   * @param string $vendortxcode
   *   SagePay VendorTxCode.
   * @param array $sagepay_data
   *   An array of data to merge in an internal array for this module.
   * @param bool $override
   *   TRUE or FALSE.
   *
   * @return array
   *   The entire updated data array for the order.
   */
  public function logTxData(
    $order_id,
    $vendortxcode,
    array $sagepay_data,
    $override = FALSE) {

    // Load the existing order data array.
    $query = \Drupal::database()->select('uc_orders', 'uco');
    $query->addField('uco', 'data');
    $query->condition('order_id', $order_id);
    $data = $query->execute()->fetchField();
    $data = unserialize($data);

    if (!isset($data['sagepay'][$vendortxcode])) {
      $data['sagepay'][$vendortxcode] = [];
    }

    if ($override) {
      $data['sagepay'][$vendortxcode] = $sagepay_data;
    }
    else {
      $data['sagepay'][$vendortxcode] = array_merge(
        $data['sagepay'][$vendortxcode],
        $sagepay_data
      );
    }

    // Save the updated data array to the database.
    $update = \Drupal::database()->update('uc_orders');
    $update->fields(['data' => serialize($data)]);
    $update->condition('order_id', $order_id);
    $update->execute();

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    $build = [];

    // Add the hidden span for the CC details if possible.
    $account = \Drupal::currentUser();
    if ($account->hasPermission('view cc details')) {
      $rows = [];

      if (!empty($order->payment_details['cc_type'])) {
        $rows[] = $this->t('Card type:') . ' ' .
        $order->payment_details['cc_type'];
      }

      if (!empty($order->payment_details['cc_owner'])) {
        $rows[] = $this->t('Card owner:') . ' ' .
        $order->payment_details['cc_owner'];
      }

      if (!empty($order->payment_details['cc_number'])) {
        $rows[] = $this->t('Card number:') . ' ' . $this->displayCardNumber(
          $order->payment_details['cc_number']
        );
      }

      if (!empty($order->payment_details['cc_start_month']) &&
        !empty($order->payment_details['cc_start_year'])) {
        $months = $order->payment_details['cc_start_month'] . '/';
        $months .= $order->payment_details['cc_start_year'];
        $rows[] = $this->t('Start date:') . ' ' . $months;
      }

      if (!empty($order->payment_details['cc_exp_month']) &&
        !empty($order->payment_details['cc_exp_year'])) {
        $expiration = $order->payment_details['cc_exp_month'] . '/';
        $expiration .= $order->payment_details['cc_exp_year'];
        $rows[] = $this->t('Expiration:') . ' ' . $expiration;
      }

      if (!empty($order->payment_details['cc_issue'])) {
        $rows[] = $this->t('Issue number:') . ' ' .
        $order->payment_details['cc_issue'];
      }

      if (!empty($order->payment_details['cc_bank'])) {
        $rows[] = $this->t('Issuing bank:') . ' ' .
        $order->payment_details['cc_bank'];
      }

      $build['cc_info'] = [
        '#markup' => implode('<br />', $rows) . '<br />',
      ];
    }

    // Add the form to process the card if applicable.
    if ($account->hasPermission('process credit cards')) {
      $build['terminal'] = [
        '#type' => 'link',
        '#title' => $this->t('Process card'),
        '#url' => Url::fromRoute('uc_sagepay.terminal_alter', [
          'uc_order' => $order->id(),
          'uc_payment_method' => $order->getPaymentMethodId(),
        ]),
      ];
    }

    return $build;
  }

}
