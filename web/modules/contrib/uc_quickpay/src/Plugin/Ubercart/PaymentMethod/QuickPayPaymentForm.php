<?php

namespace Drupal\uc_quickpay\Plugin\Ubercart\PaymentMethod;

use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Utility\Html;
use Drupal\uc_payment\PaymentMethodPluginBase;
use Drupal\uc_payment\OffsitePaymentMethodPluginInterface;

/**
 * QuickPay Ubercart gateway payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "quickpay_form_gateway",
 *   name = @Translation("Quickpay Form"),
 *   label = @Translation("Quickpay Form"),
 * )
 */
class QuickPayPaymentForm extends PaymentMethodPluginBase implements OffsitePaymentMethodPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel($label) {
    $build['label'] = [
      '#prefix' => '<span class="uc-quickpay-form">',
      '#plain_text' => $label,
      '#suffix' => '</span>',
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api' => [
        'merchant_id'     => '',
        'private_key'     => '',
        'agreement_id'    => '',
        'payment_api_key' => '',
        'pre_order_id'    => '',
      ],
      'language'          => 'en',
      'payment_method'    => '',
      'autofee'           => FALSE,
      'autocapture'       => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['api'] = [
      '#type' => 'details',
      '#title' => $this->t('API credentials'),
      '#description' => $this->t('@link for obtaining information of quickpay credentials. You need to acquire an API Signature. If you have already logged in your Quickpay then you can review your settings under the integration section of your Quickpay Gateway profile. Quickpay Form Method must needed callback URL which you need to add setting under the integration. e.g http://www.example.com/callback/', [
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
      '#description' => $this->t('This is your Merchant Account id.'),
      '#required' => TRUE,
    ];
    $form['api']['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Key'),
      '#default_value' => $this->configuration['api']['private_key'],
      '#description' => $this->t('This is your Merchant Private Key.'),
      '#required' => TRUE,
    ];
    $form['api']['agreement_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agreement ID'),
      '#default_value' => $this->configuration['api']['agreement_id'],
      '#description' => $this->t('This is your Payment Window Agreement id. The checksum must be signed with the API-key belonging to this Agreement.'),
      '#required' => TRUE,
    ];
    $form['api']['payment_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $this->configuration['api']['payment_api_key'],
      '#description' => $this->t('This is your Payment Window API key.'),
      '#required' => TRUE,
    ];
    $form['api']['pre_order_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order id prefix'),
      '#default_value' => $this->configuration['api']['pre_order_id'],
      '#description' => $this->t('Prefix of order ids. Order ids must be uniqe when sent to Quickpay, Use this to resolve clashes.'),
      '#required' => TRUE,
    ];
    $form['language'] = [
      '#type' => 'select',
      '#options' => [
        'en' => $this->t('English'),
        'da' => $this->t('Danish'),
        'de' => $this->t('German'),
        'fr' => $this->t('French'),
        'it' => $this->t('Italian'),
        'no' => $this->t('Norwegian'),
        'nl' => $this->t('Dutch'),
        'pl' => $this->t('Polish'),
        'se' => $this->t('Swedish'),
      ],
      '#title' => $this->t('Payment Language'),
      '#default_value' => !empty($this->configuration['language']) ? $this->configuration['language'] : [],
      '#description' => $this->t('Set the language of the user interface. Defaults to English.'),
    ];

    $options = [];
    // Add card label for payment method.
    foreach ($this->getQuickpayCardTypes() as $key => $card) {
      $options[$key] = $card;
    }
    $form['payment_method'] = [
      '#type' => 'checkboxes',
      '#options' => !empty($options) ? $options : [],
      '#title' => $this->t('Payment Methods'),
      '#default_value' => !empty($this->configuration['payment_method']) ? $this->configuration['payment_method'] : [],
      '#description' => $this->t('Which payment methods to accept. NOTE: Some require special agreements.'),
    ];

    $form['autofee'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autofee'),
      '#default_value' => $this->configuration['autofee'],
      '#description' => $this->t('If set 1, the fee charged by the acquirer will be calculated and added to the transaction amount.'),
    ];
    $form['autocapture'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autocapture'),
      '#default_value' => $this->configuration['autocapture'],
      '#description' => $this->t('If set to 1, the payment will be captured automatically.'),
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
      'private_key',
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
      'private_key',
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
    $this->configuration['language'] = $form_state->getValue(['settings', 'language']);
    $this->configuration['payment_method'] = $form_state->getValue(['settings', 'payment_method']);
    $this->configuration['autofee'] = $form_state->getValue(['settings', 'autofee']);
    $this->configuration['autocapture'] = $form_state->getValue(['settings', 'autocapture']);
    return parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    $payment_id = db_query("SELECT payment_id FROM {uc_payment_quickpay_callback} WHERE order_id = :id ORDER BY created_at ASC", [':id' => $order->id()])->fetchField();
    if (empty($payment_id)) {
      $payment_id = $this->t('Unknown');
    }
    $build['#markup'] = $this->t('Payment ID: @payment_id', ['@payment_id' => $payment_id]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRedirectForm(array $form, FormStateInterface $form_state, OrderInterface $order = NULL) {
    // Get billing address object.
    $bill_address = $order->getAddress('billing');
    $country = \Drupal::service('country_manager')->getCountry($bill_address->country)->getAlpha3();
    $data = [];
    // Required parameter.
    $data['version'] = 'v10';
    $data['merchant_id'] = $this->configuration['api']['merchant_id'];
    $data['agreement_id'] = $this->configuration['api']['agreement_id'];
    $data['order_id'] = $this->configuration['api']['pre_order_id'] . $order->id();
    $data['amount'] = uc_currency_format($order->getTotal(), FALSE, FALSE, FALSE);
    $data['currency'] = $order->getCurrency();
    $data['continueurl'] = Url::fromRoute('uc_quickpay.qpf_complete', [], ['absolute' => TRUE])->toString();
    $data['cancelurl'] = Url::fromRoute('uc_quickpay.qpf_cancel', [], ['absolute' => TRUE])->toString();
    $data['callbackurl'] = Url::fromRoute('uc_quickpay.qpf_callback', [], ['absolute' => TRUE])->toString();
    $data['language'] = $this->configuration['language'];

    $data['autocapture'] = $this->configuration['autocapture'] ? 1 : 0;
    // If method is selected, Payment method will be attached with form.
    if ($this->getSelectedPaymentMethod() !== FALSE) {
      $data['payment_methods'] = $this->getSelectedPaymentMethod();
    }
    $data['autofee'] = $this->configuration['autofee'] ? 1 : 0;
    // Use callback variable to verify order id.
    $data['variables[uc_order_id]'] = $order->id();
    $data['customer_email'] = $order->getEmail();
    // Invoice detail.
    if (!empty($bill_address->first_name)) {
      $data['invoice_address[name]'] = $bill_address->first_name . " " . $bill_address->last_name;
    }
    $data['invoice_address[att]'] = $bill_address->street1;

    if (!empty($bill_address->street2)) {
      $data['invoice_address[street]'] = $bill_address->street2;
    }
    $data['invoice_address[zip_code]'] = $bill_address->postal_code;

    if (!empty($bill_address->city)) {
      $data['invoice_address[city]'] = $bill_address->city;
    }
    $data['invoice_address[region]'] = $bill_address->zone;
    $data['invoice_address[country_code]'] = $country;

    if (!empty($bill_address->phone)) {
      $data['invoice_address[phone_number]'] = $bill_address->phone;
    }
    $data['invoice_address[email]'] = $order->getEmail();
    // Get tax rate.
    $tax_rate = 0;
    foreach (uc_tax_filter_rates($order) as $tax) {
      if ($tax->rate) {
        $tax_rate += $tax->rate;
      }
    }
    // Static variable for loop.
    $i = 0;
    foreach ($order->products as $item) {
      $data['basket[' . $i . '][qty]'] = $item->qty->value;
      $data['basket[' . $i . '][item_no]'] = $item->model->value;
      $data['basket[' . $i . '][item_name]'] = $item->title->value;
      $data['basket[' . $i . '][item_price]'] = uc_currency_format($item->price->value, FALSE, FALSE, FALSE);
      $data['basket[' . $i . '][vat_rate]'] = $tax_rate ? $tax_rate : 0;
      $i++;
    }
    // Checksum.
    $data['checksum'] = $this->checksumCal($data, $this->configuration['api']['payment_api_key']);
    // Add hidden field with new form.
    foreach ($data as $name => $value) {
      if (isset($value) || !empty($value)) {
        $form[$name] = ['#type' => 'hidden', '#value' => $value];
      }
    }
    $form['#action'] = 'https://payment.quickpay.net';
    $form['actions'] = ['#type' => 'actions'];
    // Text alter.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Quickpay Payment'),
      '#id' => 'quickpay-submit',
    ];
    return $form;
  }

  /**
   * Returns the set of card types which are used by this payment method.
   *
   * @return array
   *   An array with keys as needed by the chargeCard() method and values
   *   that can be displayed to the customer.
   */
  protected function getQuickpayCardTypes() {
    return [
      'dankort' => $this->t('Dankort'),
      'maestro' => $this->t('Maestro'),
      '3d-maestro' => $this->t('Maestro, using 3D-Secure'),
      '3d-maestro-dk' => $this->t('Maestro, issued in Denmark, using 3D-Secure'),
      'visa' => $this->t('Visa'),
      'visa-dk' => $this->t('Visa, issued in Denmark'),
      '3d-visa' => $this->t('Visa, using 3D-Secure'),
      '3d-visa-dk' => $this->t('Visa, issued in Denmark, using 3D-Secure'),
      'visa-electron' => $this->t('Visa Electron'),
      'visa-electron-dk' => $this->t('Visa Electron, issued in Denmark'),
      '3d-visa-electron' => $this->t('Visa Electron, using 3D-Secure'),
      '3d-visa-electron-dk' => $this->t('Visa Electron, issued in Denmark, using 3D-Secure'),
      'mastercard' => $this->t('Mastercard'),
      'mastercard-dk' => $this->t('Mastercard, issued in Denmark'),
      'mastercard-debet-dk' => $this->t('Mastercard debet card, issued in Denmark'),
      '3d-mastercard' => $this->t('Mastercard, using 3D-Secure'),
      '3d-mastercard-dk' => $this->t('Mastercard, issued in Denmark, using 3D-Secure'),
      '3d-mastercard-debet-dk' => $this->t('Mastercard debet, issued in Denmark, using 3D-Secure'),
      'amex' => $this->t('American Express'),
      'amex-dk' => $this->t('American Express, issued in Denmark'),
      'diners' => $this->t('Diners'),
      'diners-dk' => $this->t('Diners, issued in Denmark'),
      'mobilepay' => $this->t('Mobilepay'),
      'sofort' => $this->t('Sofort'),
      'jcb' => $this->t('JCB'),
      '3d-jcb' => $this->t('JCB, using 3D-Secure'),
      'fbg1886' => $this->t('Forbrugsforeningen'),
      'paypal' => $this->t('PayPal'),
      'viabill' => $this->t('ViaBill'),
    ];
  }

  /**
   * Utility function: Load QuickPay API.
   *
   * @return bool
   *   Checking prepareApi is set or not.
   */
  public function prepareApi() {
    // Checking API keys configuration.
    if (!_uc_quickpay_check_api_keys($this->getConfiguration())) {
      \Drupal::logger('uc_quickpay')->error('Quickpay API keys are not configured. Payments can not be made without them.', []);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get selected payment method.
   *
   * @return array
   *   Return selected card for accepting payment.
   */
  protected function getSelectedPaymentMethod() {
    $configurations = $this->getConfiguration();

    $methods = [];
    foreach ($configurations['payment_method'] as $key => $select_methods) {
      if (!empty($select_methods)) {
        $methods[] = $select_methods;
      }
    }

    if (!empty($methods)) {
      return implode(', ', $methods);
    }
    else {
      return FALSE;
    }

  }

  /**
   * Calculate the hash for the request.
   *
   * @var array $var
   *   The data to POST to Quickpay.
   *
   * @return string
   *   The checksum.
   */
  protected function checksumCal($params, $api_key) {
    $flattened_params = $this->flattenParams($params);
    ksort($flattened_params);
    $base = implode(' ', $flattened_params);
    return hash_hmac('sha256', $base, $api_key);
  }

  /**
   * Flatten request parameter array.
   */
  protected function flattenParams($obj, $result = [], $path = []) {
    if (is_array($obj)) {
      foreach ($obj as $k => $v) {
        $result = array_merge($result, $this->flattenParams($v, $result, array_merge($path, [$k])));
      }
    }
    else {
      $result[implode('', array_map(function ($p) {
        return "[{$p}]";
      }, $path))] = $obj;
    }
    return $result;
  }

}
