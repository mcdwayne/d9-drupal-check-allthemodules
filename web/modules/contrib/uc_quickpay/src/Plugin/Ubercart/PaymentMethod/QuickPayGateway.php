<?php

namespace Drupal\uc_quickpay\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\uc_credit\CreditCardPaymentMethodBase;
use Drupal\uc_order\OrderInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use QuickPay\QuickPay;

/**
 * QuickPay Ubercart gateway payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "quickpay_gateway",
 *   name = @Translation("Quickpay Embedded"),
 *   label = @Translation("Quickpay Embedded"),
 * )
 */
class QuickPayGateway extends CreditCardPaymentMethodBase {

  /**
   * Returns the set of transaction types allowed by this payment method.
   *
   * @return array
   *   An array with values UC_CREDIT_AUTH_ONLY, UC_CREDIT_AUTH_CAPTURE
   */
  public function getTransactionTypes() {
    return [
      UC_CREDIT_AUTH_ONLY,
      UC_CREDIT_AUTH_CAPTURE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel($label) {
    $form['label'] = [
      '#prefix' => '<span class="uc-quickpay-embedded">',
      '#plain_text' => $label,
      '#suffix' => '</sapn>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'api' => [
        'merchant_id'     => '',
        'user_api_key'    => '',
        'agreement_id'    => '',
        'payment_api_key' => '',
        'pre_order_id'    => '',
      ],
      'callback' => [
        'continue_url'    => '',
        'cancel_url'      => '',
      ],
      'autofee'           => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['api'] = [
      '#type' => 'details',
      '#title' => $this->t('API credentials'),
      '#description' => $this->t('@link for information on obtaining credentials. You need to acquire an API credentials. If you have already logged-in your quickpay profile, You can review your settings under the integration section of your quickpay profile.', [
        '@link' => Link::fromTextAndUrl($this->t('Click here'), Url::fromUri('https://manage.quickpay.net/', [
          'attributes' => ['target' => '_blank'],
        ]))->toString(),
      ]),
      '#open' => TRUE,
    ];
    $form['api']['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#default_value' => $this->configuration['api']['merchant_id'],
      '#description' => $this->t('Your merchant account id.'),
      '#required' => TRUE,
    ];
    $form['api']['user_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API User Key'),
      '#default_value' => $this->configuration['api']['user_api_key'],
      '#description' => $this->t('Your an API user key.'),
      '#required' => TRUE,
    ];
    $form['api']['agreement_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agreement ID'),
      '#default_value' => $this->configuration['api']['agreement_id'],
      '#description' => $this->t('Your payment window agreement id.'),
      '#required' => TRUE,
    ];
    $form['api']['payment_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $this->configuration['api']['payment_api_key'],
      '#description' => $this->t('Your payment window API key.'),
      '#required' => TRUE,
    ];
    $form['api']['pre_order_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order ID Prefix'),
      '#default_value' => $this->configuration['api']['pre_order_id'],
      '#description' => $this->t('Prefix of order id. Order id must be unique when sent to Quickpay, Use this to resolve clashes.'),
      '#required' => TRUE,
    ];
    $form['callback'] = [
      '#type' => 'details',
      '#title' => $this->t('Callback'),
      '#description' => $this->t('Callback URL&apos;s. (Optional)'),
      '#open' => TRUE,
    ];
    $form['callback']['continue_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Continue URL'),
      '#default_value' => $this->configuration['callback']['continue_url'],
      '#description' => $this->t('The customer will be redirected to this URL upon a successful payment. No data will be send to this URL.'),
    ];
    $form['callback']['cancel_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cancel URL'),
      '#default_value' => $this->configuration['callback']['cancel_url'],
      '#description' => $this->t('The customer will be redirected to this URL if the customer cancels the payment. No data will be send to this URL.'),
    ];
    $form['autofee'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autofee'),
      '#default_value' => $this->configuration['autofee'],
      '#description' => $this->t('If set 1, the fee charged by the acquirer will be calculated and added to the transaction amount.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Numeric validation for all the id's.
    $element_ids = [
      'merchant_id',
      'agreement_id',
      'pre_order_id',
    ];
    foreach ($element_ids as $element_id) {
      $raw_key = $form_state->getValue(['settings', 'api', $element_id]);
      if (!is_numeric($raw_key)) {
        $form_state->setError($element_ids, $this->t('The @name @value is not valid. It must be numeric',
          [
            '@name' => $element_id,
            '@value' => $raw_key,
          ]
        ));
      }
    }
    // Key's validation.
    $element_keys = [
      'user_api_key',
      'payment_api_key',
    ];
    foreach ($element_keys as $element_name) {
      $raw_key = $form_state->getValue(['settings', 'api', $element_name]);
      $sanitized_key = $this->trimKey($raw_key);
      $form_state->setValue(['settings', $element_name], $sanitized_key);
      if (!$this->validateKey($form_state->getValue(['settings', $element_name]))) {
        $form_state->setError($element_keys, $this->t('@name does not appear to be a valid Quickpay key',
          [
            '@name' => $element_name,
          ]
        ));
      }
    }
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * Checking vaildation keys of payment gateway.
   */
  protected function trimKey($key) {
    $key = trim($key);
    $key = Html::escape($key);
    return $key;
  }

  /**
   * Validate QuickPay key.
   *
   * @var $key
   *   Key which passing on admin side.
   *
   * @return bool
   *   Return that is key is vaild or not.
   */
  public function validateKey($key) {
    $valid = preg_match('/^[a-zA-Z0-9_]+$/', $key);
    return $valid;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $elements = [
      'merchant_id',
      'user_api_key',
      'agreement_id',
      'payment_api_key',
      'pre_order_id',
    ];
    foreach ($elements as $item) {
      $this->configuration['api'][$item] = $form_state->getValue([
        'settings',
        'api',
        $item,
      ]);
    }
    $this->configuration['callback']['continue_url'] = $form_state->getValue([
      'settings',
      'callback',
      'continue_url',
    ]);
    $this->configuration['callback']['cancel_url'] = $form_state->getValue([
      'settings',
      'callback',
      'cancel_url',
    ]);
    $this->configuration['autofee'] = $form_state->getValue(['settings', 'autofee']);
    return parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function cartProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $logged_in = \Drupal::currentUser();

    if (!$form_state->hasValue(['panes', 'payment', 'details', 'cc_number'])) {
      return;
    }
    // Fetch the CC details from the $_POST directly.
    $cc_data = $form_state->getValue(['panes', 'payment', 'details']);

    // Recover cached CC data in form state, if it exists.
    if (isset($cc_data['payment_details_data'])) {
      $cache = uc_credit_cache(base64_decode($cc_data['payment_details_data']));
      unset($cc_data['payment_details_data']);
    }

    // Create date as required in quickpay.
    if (!empty($cc_data['cc_exp_month']) && !empty($cc_data['cc_exp_year'])) {
      $month_format = sprintf('%02d', $cc_data['cc_exp_month']);
      $year_format = Unicode::substr($cc_data['cc_exp_year'], -2);
      $quickpay_date = $year_format . $month_format;
    }

    // Card init request.
    $card_init_response = $this->payClient()->request->post('/cards');
    // Return Response.
    $card_response = $card_init_response->asObject();
    // Checking card request is Accepted or not.
    if ($card_init_response->httpStatus() === 201) {
      // Authorize card detail.
      $card_data = [
        'card[number]' => $cc_data['cc_number'],
        'card[expiration]' => $quickpay_date,
        'card[cvd]' => $cc_data['cc_cvv'],
      ];
      $card_object = $this->payClient()->request->post("/cards/{$card_response->id}/authorize?synchronized", $card_data);
      // Response authorize request.
      $card_detail = $card_object->asObject();
      $card_status = $card_object->httpStatus();

      if ($card_status == 200) {
        // Request for token.
        $card_tokens_request = $this->payClient()->request->post("/cards/{$card_detail->id}/tokens");
        $card_token = $card_tokens_request->asObject();
        $uc_token = $card_token->token;
      }
    }
    // Go ahead and put the CC data in the payment details array.
    $order->payment_details = $cc_data;
    // Initialize the encryption key and class.
    $key = uc_credit_encryption_key();
    $crypt = \Drupal::service('uc_store.encryption');
    // Store the encrypted details in the session for the next pageload.
    // We are using base64_encode() because the encrypt function works with a
    // limited set of characters, not supporting the full Unicode character
    // set or even extended ASCII characters that may be present.
    // base64_encode() converts everything to a subset of ASCII, ensuring that
    // the encryption algorithm does not mangle names.
    $session = \Drupal::service('session');
    $session->set('sescrd', $crypt->encrypt($key, base64_encode(serialize($order->payment_details))));
    // Log any errors to the watchdog.
    uc_store_encryption_errors($crypt, 'uc_credit');
    if (!empty($uc_token)) {
      \Drupal::service('user.private_tempstore')->get('uc_quickpay')->set('card_token', $uc_token);
    }
    return parent::cartProcess($order, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function cartReviewTitle() {
    return $this->t('Quickpay Credit Card');
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    $build = [];
    // Add the hidden span for the CC details if possible.
    $payment_id = db_query("SELECT payment_id FROM {uc_payment_quickpay_callback} WHERE order_id = :id ORDER BY created_at ASC", [':id' => $order->id()])->fetchField();
    $account = \Drupal::currentUser();
    if ($account->hasPermission('view cc details')) {
      $rows = [];
      if (!empty($order->payment_details['cc_type'])) {
        $rows[] = $this->t('Card type:') . $order->payment_details['cc_type'];
      }
      if (!empty($order->payment_details['cc_number'])) {
        $rows[] = $this->t('Card number:') . $this->displayCardNumber($order->payment_details['cc_number']);
      }

      if (empty($payment_id)) {
        $rows[] = $this->t('Payment ID: @payment_id', ['@payment_id' => 'Unknown']);
      }
      else {
        $rows[] = $this->t('Payment ID: @payment_id', ['@payment_id' => $payment_id]);
      }
      $build['cc_info'] = [
        '#markup' => implode('<br />', $rows) . '<br />',
      ];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function processPayment(OrderInterface $order, $amount, $txn_type, $reference = NULL) {
    // Ensure the cached details are loaded.
    // @todo Figure out which parts of this call are strictly necessary.
    $this->orderLoad($order);
    // Calling chargeCard.
    $result = $this->chargeCard($order, $amount, $txn_type, $reference);
    // If the payment processed successfully.
    if ($result['success'] === TRUE) {
      // Log the payment to the order if not disabled.
      uc_payment_enter($order->id(), $this->getPluginId(), $amount,
        empty($result['uid']) ? 0 : $result['uid'],
        empty($result['message']) ? '' : $result['message'],
        empty($result['comment']) ? '' : $result['comment']
      );
      uc_order_comment_save($order->id(), $order->getOwnerId(), $result['message'], 'admin');
    }
    else {
      // Otherwise display the failure message in the logs.
      \Drupal::logger('uc_payment')->warning('Quickpay payment has been failed for order @order_id: @message',
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
  protected function chargeCard(OrderInterface $order, $amount, $txn_type, $reference = NULL) {
    if (!$this->prepareApi()) {
      $result = [
        'success' => FALSE,
        'comment' => $this->t('Quickpay payment API is not found.'),
        'message' => $this->t('Quickpay payment API is not found. Please contact the site administrator.'),
        'uid' => $order->getOwnerId(),
        'order_id' => $order->id(),
      ];
      return $result;
    }
    // Get tax rate.
    $tax_rate = 0;
    foreach (uc_tax_filter_rates($order) as $tax) {
      if ($tax->rate) {
        $tax_rate += $tax->rate;
      }
    }
    // Cart product.
    $cart_content = [];
    foreach ($order->products as $item) {
      $cart_content[] = [
        'qty' => $item->qty->value,
        'item_no' => $item->model->value,
        'item_name' => $item->title->value,
        'item_price' => uc_currency_format($item->price->value, FALSE, FALSE, FALSE),
        'vat_rate' => $tax_rate ? $tax_rate : 0,
      ];
    }
    $order_total = uc_currency_format($amount, FALSE, FALSE, FALSE);
    $country = \Drupal::service('country_manager')->getCountry($order->getAddress('billing')->country)->getAlpha3();
    $card_token = \Drupal::service('user.private_tempstore')->get('uc_quickpay')->get('card_token');
    // Create payment request to get payment_id.
    $payment_req_form = [
      'currency' => $order->getCurrency(),
      'order_id' => $this->configuration['api']['pre_order_id'] . $order->id(),
      'invoice_address' => [
        'email'    => $order->getEmail(),
        'name'     => $order->getAddress('billing')->first_name . ' ' . $order->getAddress('billing')->last_name,
        'street'   => $order->getAddress('billing')->street1,
        'city'     => $order->getAddress('billing')->city ? $order->getAddress('billing')->city : '',
        'zip_code' => $order->getAddress('billing')->postal_code,
        'region'   => $order->getAddress('billing')->zone,
        'country_code'  => $country,
        'phone_number'  => $order->getAddress('billing')->phone ? $order->getAddress('billing')->phone : '',
      ],
      'basket' => $cart_content,
    ];
    $payment_request = $this->payClient()->request->post('/payments', $payment_req_form);
    // Return Response from pyment request.
    $payment_req_response = $payment_request->asObject();
    // Checking payment request is accepted or not.
    if ($payment_request->httpStatus() === 201) {
      // Authorise the payment.
      $payment_auth_data = [
        'amount' => $order_total,
        'card'   => [
          'token' => $card_token,
        ],
        'autofee' => $this->configuration['autofee'] ? 1 : 0,
      ];
      $payment_auth_request = $this->payClient()->request->post("/payments/{$payment_req_response->id}/authorize?synchronized", $payment_auth_data);
      // Authorize response.
      $authorize_data = $payment_auth_request->asObject();
      // Checking payment authorize is success or not.
      if ($payment_auth_request->isSuccess()) {
        if ($txn_type == 'auth_capture') {
          // To capture payment using capture class below.
          $payment_capture = $this->capture($order, $payment_req_response->id, $authorize_data->operations[0]->amount);
          $message = $this->t('Quickpay payment has been successful amount: @amount.', ['@amount' => uc_currency_format($amount)]);
          uc_order_comment_save($order->id(), $order->getOwnerId(), $message, 'admin');
          // Get string length.
          $order_length = Unicode::strlen((string) $order->id());
          $order_id = Unicode::substr($payment_capture->order_id, -$order_length);
          // Update callback in database.
          db_insert('uc_payment_quickpay_callback')
            ->fields([
              'order_id' => $order_id,
              'payment_id' => $payment_capture->id,
              'merchant_id' => $payment_capture->merchant_id,
              'payment_type' => $payment_capture->metadata->type,
              'payment_brand' => $payment_capture->metadata->brand,
              'payment_amount' => $payment_capture->operations[0]->amount,
              'payment_status' => $payment_capture->operations[0]->qp_status_msg,
              'customer_email' => $payment_capture->invoice_address->email,
              'created_at' => REQUEST_TIME,
            ])
            ->execute();
          // Store result.
          $result = [
            'success' => TRUE,
            'comment' => $this->t('Quickpay payment has been charged with the payment capture,'),
            'message' => $this->t('Quickpay payment has been successful with payment capture.'),
            'uid' => $order->getOwnerId(),
          ];
          // Return result.
          return $result;
        }
        else {
          // Get string length.
          $order_length = Unicode::strlen((string) $order->id());
          $order_id = Unicode::substr($authorize_data->order_id, -$order_length);
          // Update callback in database.
          db_insert('uc_payment_quickpay_callback')
            ->fields([
              'order_id' => $order_id,
              'payment_id' => $authorize_data->id,
              'merchant_id' => $authorize_data->merchant_id,
              'payment_type' => $authorize_data->metadata->type,
              'payment_brand' => $authorize_data->metadata->brand,
              'payment_amount' => $authorize_data->operations[0]->amount,
              'payment_status' => $authorize_data->operations[0]->qp_status_msg,
              'customer_email' => $authorize_data->invoice_address->email,
              'created_at' => REQUEST_TIME,
            ])
            ->execute();
          // Store result.
          $result = [
            'success' => TRUE,
            'comment' => $this->t('Quickpay Payment has been charged without the capture,'),
            'message' => $this->t('Quickpay payment has been successful without payment capture.'),
            'uid' => $order->getOwnerId(),
          ];
          // Return result.
          return $result;
        }
      }
      else {
        // Store result.
        $result = [
          'success' => FALSE,
          'comment' => $this->t("Quickpay payment request has been successful but payment authenticate is failed"),
          'message' => $this->t("Quickpay payment request has been successful but payment is not authorized for order @order:", ['@order' => $order->id()]),
          'uid' => $order->getOwnerId(),
        ];
        \Drupal::logger('uc_quickpay')->notice($authorize_data->message);
        // Order comment.
        uc_order_comment_save($order->id(), $order->getOwnerId(), $authorize_data->message, 'admin');
        // Return result.
        return $result;
      }
    }
    else {
      // Error for payment->message.
      drupal_set_message($payment_req_response->message, 'error', FALSE);
      // Order comment.
      uc_order_comment_save($order->id(), $order->getOwnerId(), $payment_req_response->message, 'admin');
      // Store result.
      $result = [
        'success' => FALSE,
        'comment' => $this->t("Quickpay payment is authorized"),
        'message' => $this->t("Quickpay payment is authorized for order @order. Please try again with new order id.", ['@order' => $order->id()]),
        'uid' => $order->getOwnerId(),
      ];
      return $result;
    }
  }

  /**
   * Return Quickpay client.
   *
   * @return Quickpay
   *   The client.
   */
  public function payClient() {
    if (!class_exists('QuickPay\QuickPay')) {
      \Drupal::logger('uc_quickpay')->error('Quickpay library is not installed. Please install quickpay library.', []);
      return FALSE;
    }
    // Create quickpay client.
    $payment_api_key = $this->configuration['api']['payment_api_key'];
    return new QuickPay(":{$payment_api_key}");
  }

  /**
   * Return Quickpay client.
   *
   * @return Quickpay
   *   The client.
   */
  public function captureClient() {
    $user_api_key = $this->configuration['api']['user_api_key'];
    return new QuickPay(":{$user_api_key}");
  }

  /**
   * Capture on an authorised payment.
   */
  public function capture($order, $payment_id, $amount) {
    // Capture payment.
    $capturedata = [
      'amount' => $amount,
    ];
    $capture_request = $this->captureClient()->request->post("/payments/{$payment_id}/capture?synchronized", $capturedata);
    // Response of capture payment.
    $capture_data = $capture_request->asObject();
    // Cheking response sucees.
    if (!$capture_request->isSuccess()) {
      \Drupal::logger('uc_quickpay')->notice($capture_data->message);
      // Order comment.
      uc_order_comment_save($order->id(), $order->getOwnerId(), $capture_data->message, 'admin');
      // Result store.
      $result = [
        'success' => FALSE,
        'comment' => $this->t("Payment capture has been failed"),
        'message' => $this->t("Quickpay payment has not been captured for order @order: @message", [
          '@order' => $order->id(),
          '@message' => $capture_data->message,
        ]),
        'uid' => $order->getOwnerId(),
      ];
      // Return result.
      return $result;
    }
    return $capture_data;
  }

  /**
   * Utility function: Load QuickPay API.
   *
   * @return bool
   *   Checking PrepareApi is set or not.
   */
  public function prepareApi() {
    // Checking API keys configuration.
    if (!uc_quickpay_check_api_keys_and_ids($this->getConfiguration())) {
      \Drupal::logger('uc_quickpay')->error('Quickpay payment API key&apos;s are not configured. Payment cannot be made without them.', []);
      return FALSE;
    }
    return TRUE;
  }

}
