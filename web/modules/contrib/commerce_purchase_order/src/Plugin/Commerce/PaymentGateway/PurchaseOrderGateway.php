<?php

namespace Drupal\commerce_purchase_order\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\HasPaymentInstructionsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsVoidsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_price\Price;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the On-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "purchase_order_gateway",
 *   label = "Purchase Orders",
 *   display_label = "Purchase Orders",
 *    forms = {
 *     "receive-payment" =
 *   "Drupal\commerce_payment\PluginForm\PaymentReceiveForm",
 *     "add-payment-method" =
 *   "Drupal\commerce_purchase_order\PluginForm\PurchaseOrder\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"purchase_order"},
 *   payment_type = "payment_purchase_order"
 * )
 */
class PurchaseOrderGateway extends PaymentGatewayBase implements OnsitePaymentGatewayInterface, HasPaymentInstructionsInterface, SupportsVoidsInterface, SupportsRefundsInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getCreditCardTypes() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'instructions' => [
        'value' => '',
        'format' => 'plain_text',
      ],
      'limit_open' => 1.0,
      'user_approval' => TRUE,
      'payment_method_types' => ['purchase_order'],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['limit_open'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit maximum open purchase orders'),
      '#default_value' => $this->configuration['limit_open'],
      '#description' => $this->t('During the authorization state at checkout, the transaction is denied if the number of unpaid payments exceeds this number.'),
      '#min' => 1,
      '#step' => 1.0,
      '#size' => 3,
    ];
    $form['user_approval'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Purchase order users require approval in the user account settings.'),
      '#default_value' => $this->configuration['user_approval'],
    ];
    $form['instructions'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Payment instructions'),
      '#description' => $this->t('Shown the end of checkout, after the customer has placed their order.'),
      '#default_value' => $this->configuration['instructions']['value'],
      '#format' => $this->configuration['instructions']['format'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['instructions'] = $values['instructions'];
      $this->configuration['limit_open'] = $values['limit_open'];
      $this->configuration['user_approval'] = $values['user_approval'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentOperations(PaymentInterface $payment) {
    $payment_state = $payment->getState()->value;
    $operations = [];
    $operations['receive'] = [
      'title' => $this->t('Receive'),
      'page_title' => $this->t('Receive payment'),
      'plugin_form' => 'receive-payment',
      'weight' => -99,
      'access' => $payment_state == 'completed',
    ];
    $operations['void'] = [
      'title' => $this->t('Void'),
      'page_title' => $this->t('Void payment'),
      'plugin_form' => 'void-payment',
      'access' => $payment_state == 'completed',
      'weight' => 90,
    ];
    $operations['refund'] = [
      'title' => $this->t('Refund'),
      'page_title' => $this->t('Refund payment'),
      'plugin_form' => 'refund-payment',
      'access' => in_array($payment_state, ['completed', 'partially_refunded']),
    ];

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentInstructions(PaymentInterface $payment) {
    $instructions = [];
    if (!empty($this->configuration['instructions']['value'])) {
      $instructions = [
        '#type' => 'processed_text',
        '#text' => $this->configuration['instructions']['value'],
        '#format' => $this->configuration['instructions']['format'],
      ];
    }

    return $instructions;
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $this->authorizePayment($payment);
    $this->assertAuthorized($payment);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);
    $payment->setState('completed');
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function receivePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed']);

    // If not specified, use the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $payment->state = 'paid';
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['completed']);

    $payment->state = 'voided';
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $required_keys = [
      'number',
    ];
    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }
    // Not re-usable because we will store the PO number in the method.
    $payment_method->setReusable(FALSE);
    $payment_method->po_number = $payment_details['number'];
    $payment_method->setExpiresTime(strtotime("+60 day"));
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // There is no remote system.  These are only stored locally.
    $payment_method->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->state = 'partially_refunded';
    }
    else {
      $payment->state = 'refunded';
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }

  /**
   * Authorizes payment based on settings in the gateway configuration.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to authorize.
   */
  protected function authorizePayment(PaymentInterface $payment) {
    $customer = $payment->getOrder()->getCustomer();
    if ($this->configuration['user_approval']) {
      $user_approved = ($customer->hasField('field_purchase_orders_authorized') && $customer->field_purchase_orders_authorized->first()->value);
    }
    else {
      // There is no user approval.
      $user_approved = TRUE;
    }
    $user_po_methods = $this->entityTypeManager->getStorage('commerce_payment_method')
      ->loadByProperties([
        'uid' => $customer->id(),
        'type' => 'purchase_order',
      ]);
    if (!empty($user_po_methods)) {
      $user_po_method_ids = [];
      foreach ($user_po_methods as $method) {
        $user_po_method_ids[] = $method->id();
      }
      $payment_query = $this->entityTypeManager->getStorage('commerce_payment')
        ->getQuery();
      $payment_query->condition('payment_method', $user_po_method_ids, 'IN')
        ->condition('state', 'completed')
        ->count();
      $open_po_count = $payment_query->execute();
    }
    else {
      $open_po_count = 0;
    }
    if ($user_approved && ($open_po_count < $this->configuration['limit_open'])) {
      $payment->setState('authorized');
      $payment->setAuthorizedTime($this->time->getRequestTime());
    }
  }

  /**
   * Asserts that the payment successfully authorized.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   *
   * @throws \Drupal\commerce_payment\Exception\HardDeclineException
   *   Thrown when the payment method did not authorize.
   */
  protected function assertAuthorized(PaymentInterface $payment) {
    if ($payment->getState()->value != 'authorized') {
      throw new HardDeclineException('The purchase order failed to authorized.  Please contact a site administrator.');
    }
  }

}
