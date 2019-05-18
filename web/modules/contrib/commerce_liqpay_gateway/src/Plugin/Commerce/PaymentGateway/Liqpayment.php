<?php

namespace Drupal\commerce_liqpay_gateway\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\commerce_liqpay_gateway\Controller\LiqPayStatusRequestSender;

/**
 * Provides the Off-site Redirect payment Liqapy gateway.
 *
 * @CommercePaymentGateway(
 *   id = "liq_payment_redirect",
 *   label = "Liqpay (Off-site redirect)",
 *   display_label = "Liqpay",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_liqpay_gateway\PluginForm\OffsiteRedirect\LiqpaymentForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa",
 *   },
 * )
 */
class Liqpayment extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config   = \Drupal::config('commerce_liqpay_gateway.settings');
    $defaults = [];

    foreach ($this->commerceLiqpayTransactionStatuses() as $status_name => $status) {
      $defaults['message_' . $status_name] = $status['message'];
    }

    $defaults = $defaults + [
      'version'     => $config->get('commerce_liqpay_gateway.version'),
      'sandbox'     => FALSE,
      'public_key'  => '',
      'private_key' => '',
      'action_url'  => $config->get('commerce_liqpay_gateway.checkout_url'),
      'action'      => $config->get('commerce_liqpay_gateway.action'),
      'api_url'     => $config->get('commerce_liqpay_gateway.api_url'),
      'description' => 'Ordered # [commerce_order:order_id] at [site:name].',
    ];
    return $defaults + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['version'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('API version'),
      '#default_value' => $this->configuration['version'],
      '#maxlength'     => 1,
      '#required'      => TRUE,
    ];

    $form['sandbox'] = [
      '#title'         => $this->t('Sandbox mode'),
      '#description'   => $this->t('Enables the testing environment for developers. Card will not be credited during payment process. All test payments will have `sandbox` status - success test payment.'),
      '#default_value' => $this->configuration['sandbox'],
      '#type'          => 'checkbox',
    ];

    $form['public_key'] = [
      '#title'         => $this->t('Public key'),
      '#description'   => $this->t('Key is the unique store identifier. You can get the key in @link.', [
        '@link' => Link::fromTextAndUrl($this->t('the store settings'), Url::fromUri('https://www.liqpay.com/en/admin/business'))->toString(),
      ]),
      '#default_value' => $this->configuration['public_key'],
      '#type'          => 'textfield',
      '#required'      => TRUE,
    ];

    $form['private_key'] = [
      '#title'         => $this->t('Private key'),
      '#description'   => $this->t('Key is the unique store identifier. You can get the key in @link.', [
        '@link' => Link::fromTextAndUrl(t('the store settings'), Url::fromUri('https://www.liqpay.com/en/admin/business'))->toString(),
      ]),
      '#default_value' => $this->configuration['private_key'],
      '#type'          => 'textfield',
      '#required'      => TRUE,
    ];

    $form['action'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Action'),
      '#default_value' => $this->configuration['action'],
      '#required'      => TRUE,
    ];

    $form['action_url'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Action url'),
      '#description'   => $this->t('Payment Action url required by Liqpay.'),
      '#default_value' => $this->configuration['action_url'],
      '#required'      => TRUE,
    ];

    $form['api_url'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('API url'),
      '#description'   => $this->t('Payment API url required by Liqpay. This API url needed to verify payment status.'),
      '#default_value' => $this->configuration['api_url'],
      '#required'      => TRUE,
    ];

    $form['description'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Payment description'),
      '#description'   => $this->t('Payment description required by Liqpay. This description will be available in LiqPay interface. You can use token replacement patterns for adding order id, site name, etc.'),
      '#default_value' => $this->configuration['description'],
      '#required'      => TRUE,
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['token_help'] = [
        '#type'  => 'details',
        '#title' => $this->t('Available tokens'),
      ];

      $form['token_help']['token_tree_link'] = [
        '#theme'       => 'token_tree_link',
        '#token_types' => ['commerce_order'],
      ];
    }

    $form['messages'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Messages'),
      '#description' => $this->t('You can find more details about LiqPay statuses at @link.', [
        '@link' => Link::fromTextAndUrl(t('API reference'), Url::fromUri('https://www.liqpay.com/en/doc/checkout'))->toString(),
      ]),
      '#collapsible' => TRUE,
      '#collapsed'   => TRUE,
    ];

    foreach ($this->commerceLiqpayTransactionStatuses() as $status_name => $status) {
      $form['messages']['message_' . $status_name] = [
        '#type'          => 'textarea',
        '#rows'          => 2,
        '#title'         => $status['title'],
        '#description'   => $this->t('API status machine name: @status', [
          '@status' => $status_name,
        ]),
        '#default_value' => $this->configuration['message_' . $status_name] === '' ? $status['message'] : $this->configuration['message_' . $status_name],

      ];
    }
    return $form;
  }

  /**
   * Returns an array of all handled LiqPay transaction statuses.
   *
   * See https://www.liqpay.com/en/doc/callback for details.
   *
   * @param string $name
   *   Status name.
   *
   * @return array|bool
   *   FALSE on error validation array otherwise.
   */
  public function commerceLiqpayTransactionStatuses($name = NULL) {
    $statuses = [
      // Success statuses.
      'success'    => [
        'status'  => 'success',
        'title'   => $this->t('Success'),
        'message' => $this->t('Payment completed.'),
      ],
      'reversed'   => [
        'status'  => 'reversed',
        'title'   => $this->t('Reversed'),
        'message' => $this->t('Payment amount reversed to card holder.'),
      ],
      'sandbox'    => [
        'status'  => 'sandbox',
        'title'   => $this->t('Sandbox'),
        'message' => $this->t('Transaction marked as sandbox.'),
      ],

      // Failure status.
      'failure'    => [
        'status'  => 'failure',
        'title'   => $this->t('Failure'),
        'message' => $this->t('Payment marked as failed.'),
      ],
      'error'      => [
        'status'  => 'error',
        'title'   => $this->t('Error'),
        'message' => $this->t('Payment finished with error.'),
      ],

      // Pending statuses.
      'processing' => [
        'status'  => 'processing',
        'title'   => $this->t('Pending'),
        'message' => $this->t('Payment marked as processed.'),
      ],
      'cash_wait'  => [
        'status'  => 'cash_wait',
        'title'   => $this->t('Waiting for cash'),
        'message' => $this->t('Order waiting for a payment with self-service terminal.'),
      ],
    ];

    if (NULL !== $name) {
      if (isset($statuses[$name])) {
        return $statuses[$name];
      }
      return FALSE;
    }

    return $statuses;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $required_fields = [
      'public_key',
      'private_key',
      'description',
      'api_url',
      'action_url',
    ];
    foreach ($required_fields as $key) {
      if (empty($values[$key])) {
        drupal_set_message(t('LiqPay service is not configured for use. Please contact an administrator to resolve this issue.'), 'error');
        return FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values                             = $form_state->getValue($form['#parents']);
      $this->configuration['version']     = $values['version'];
      $this->configuration['sandbox']     = $values['sandbox'];
      $this->configuration['public_key']  = $values['public_key'];
      $this->configuration['private_key'] = $values['private_key'];
      $this->configuration['action']      = $values['action'];
      $this->configuration['action_url']  = $values['action_url'];
      $this->configuration['api_url']     = $values['api_url'];
      $this->configuration['description'] = $values['description'];

      foreach ($values['messages'] as $message_name => $message) {
        $this->configuration[$message_name] = $message;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    // Receiving transaction status.
    if (!$data = $this->receivingLiqpayTransaction($order)) {
      drupal_set_message($this->t('Invalid Transaction. Please try again'), 'error');
      return $this->onCancel($order, $request);
    }
    else {
      $data = $this->receivingLiqpayTransaction($order);

      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      $payment         = $payment_storage->create([
        'state'           => $data['status'],
        'amount'          => $order->getTotalPrice(),
        'payment_gateway' => $this->entityId,
        'order_id'        => $data['order_id'],
        'remote_id'       => $data['liqpay_order_id'],
        'remote_state'    => $data['status'],
      ]);
      $payment->save();
      drupal_set_message($data['message']);
    }
  }

  /**
   * Getting configuration.
   *
   * @return array
   *   Configuration array.
   */
  public function getStoreDataConfiguration() {
    $config = \Drupal::config('commerce_liqpay_gateway.settings');

    return $store_data = [
      'public_key'   => isset($this->configuration['public_key']) ? $this->configuration['public_key'] : '',
      'private_key'  => isset($this->configuration['private_key']) ? $this->configuration['private_key'] : '',
      'api_url'      => isset($this->configuration['api_url']) ? $this->configuration['api_url'] : $config->get('commerce_liqpay_gateway.api_url'),
      'checkout_url' => isset($this->configuration['checkout_url']) ? $this->configuration['checkout_url'] : $config->get('commerce_liqpay_gateway.checkout_url'),
      'version'      => isset($this->configuration['version']) ? $this->configuration['version'] : $config->get('commerce_liqpay_gateway.version'),
    ];
  }

  /**
   * Receiving Transaction for module payment.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Current order.
   *
   * @return array|bool
   *   FALSE on error validation array otherwise.
   */
  protected function receivingLiqpayTransaction(OrderInterface $order) {
    $store_data = $this->getStoreDataConfiguration();

    $liqpay = new LiqPayStatusRequestSender($store_data);

    // Check transaction status.
    $data = $liqpay->api("request", [
      'action'   => 'status',
      'version'  => isset($this->configuration['version']) ? $this->configuration['version'] : $store_data['version'],
      'order_id' => $order->id(),
    ]);

    if (!$data) {
      return FALSE;
    }

    $status = $this->commerceLiqpayTransactionStatuses($data['status']);
    if (isset($status) && FALSE === $status) {
      return FALSE;
    }

    // Check amount and currency.
    if (!isset($data['currency']) && !isset($data['amount'])) {
      return FALSE;
    }

    $transaction_currency = $liqpay->isSupportedCurrency($data['currency']) ? $data['currency'] : FALSE;
    $transaction_amount   = $data['amount'];
    $order_currency       = $order->getTotalPrice()->getCurrencyCode();
    $order_amount         = $order->getTotalPrice()->getNumber();

    if (!$this->validateCurrency($transaction_currency, $order_currency) || !$this->validateAmount($transaction_amount, $order_amount)) {
      return FALSE;
    }

    // Find out a message for this transaction.
    $message_liqpay = $this->configuration['message_' . $status['status']];
    if (!empty($message_liqpay)) {
      $data['message'] = $message_liqpay;
    }
    else {
      $data['message'] = $status['message'];
    }

    // Everything is ok with this transaction.
    return $data;
  }

  /**
   * Currency default validation.
   *
   * @param string $transaction_currency
   *   Currency from Liqpay.
   * @param string $order_currency
   *   Currency from order.
   *
   * @return bool
   *   TRUE on successful validation FALSE otherwise.
   */
  protected function validateCurrency($transaction_currency, $order_currency) {
    if ($transaction_currency != $order_currency) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Amount default validation.
   *
   * @param float $transaction_amount
   *   Amount from Liqpay.
   * @param float $order_amount
   *   Amount from order.
   *
   * @return bool
   *   TRUE on successful validation FALSE otherwise.
   */
  protected function validateAmount($transaction_amount, $order_amount) {
    if ($transaction_amount != $order_amount) {
      return FALSE;
    }
    return TRUE;
  }

}
