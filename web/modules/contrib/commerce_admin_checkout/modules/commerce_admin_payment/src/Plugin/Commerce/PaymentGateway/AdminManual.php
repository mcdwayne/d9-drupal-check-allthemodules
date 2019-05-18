<?php

namespace Drupal\commerce_admin_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_multi_payment\Entity\StagedPaymentInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Manual payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "admin_manual",
 *   label = "Admin Manual",
 *   display_label = "Admin Manual",
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   },
 *   forms = {
 *     "add-payment" = "Drupal\commerce_admin_payment\PluginForm\AdminManualPaymentAddForm",
 *     "receive-payment" = "Drupal\commerce_payment\PluginForm\PaymentReceiveForm",
 *   },
 *   payment_type = "payment_admin_manual",
 * )
 */
class AdminManual extends PaymentGatewayBase implements AdminManualPaymentGatewayInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'instructions' => [
          'value' => '',
          'format' => 'plain_text',
        ],
        'automatically_received' => TRUE,
        'allow_negative' => FALSE,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['automatically_received'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Payments of this type are automatically marked as received'),
      '#default_value' => $this->configuration['automatically_received'],
    ];
    $form['allow_negative'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow negative value payments'),
      '#default_value' => $this->configuration['allow_negative'],
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
      $this->configuration['automatically_received'] = (bool) $values['automatically_received'];
      $this->configuration['allow_negative'] = (bool) $values['allow_negative'];
    }
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
  public function buildPaymentOperations(PaymentInterface $payment) {
    $payment_state = $payment->getState()->value;
    $operations = [];
    $operations['receive'] = [
      'title' => $this->t('Receive'),
      'page_title' => $this->t('Receive payment'),
      'plugin_form' => 'receive-payment',
      'access' => $payment_state == 'pending',
    ];
    $operations['void'] = [
      'title' => $this->t('Void'),
      'page_title' => $this->t('Void payment'),
      'plugin_form' => 'void-payment',
      'access' => $payment_state == 'pending',
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
  public function createPayment(PaymentInterface $payment, $received = FALSE) {
    $this->assertPaymentState($payment, ['new']);

    $payment->state = $received ? 'completed' : 'pending';
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function receivePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['pending']);

    // If not specified, use the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $payment->state = 'completed';
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['pending', 'authorization']);

    $payment->state = 'voided';
    $payment->save();
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
   * @inheritDoc
   */
  public function multiPaymentDisplayLabel() {
    return $this->getDisplayLabel();
  }

  /**
   * @inheritDoc
   */
  public function multiPaymentAdjustmentLabel(StagedPaymentInterface $staged_payment) {
    return $this->getDisplayLabel();
  }
  /**
   * @inheritDoc
   */
  public function multiPaymentAuthorizePayment(StagedPaymentInterface $staged_payment, PaymentInterface $payment) {
    $payment->set('description', $staged_payment->getData('description'));
    $this->createPayment($payment, !empty($this->configuration['automatically_received']));
    $staged_payment->setPayment($payment);
    $payment->setState('authorization');
    $staged_payment->setState(StagedPaymentInterface::STATE_AUTHORIZATION);
    $staged_payment->save();
  }

  /**
   * @inheritDoc
   */
  public function multiPaymentVoidPayment(StagedPaymentInterface $staged_payment) {
    if ($staged_payment->getState() == StagedPaymentInterface::STATE_AUTHORIZATION && !empty($staged_payment->getPayment()) && $staged_payment->getPayment()->getState()->getString() == 'authorization') {
      $this->voidPayment($staged_payment->getPayment());
    }
    $staged_payment->setState(StagedPaymentInterface::STATE_AUTHORIZATION_VOIDED);
    $staged_payment->save();
  }

  /**
   * @inheritDoc
   */
  public function multiPaymentExpirePayment(StagedPaymentInterface $staged_payment) {
    if ($staged_payment->getState() == StagedPaymentInterface::STATE_AUTHORIZATION && !empty($staged_payment->getPayment()) && $staged_payment->getPayment()->getState()->getString() == 'authorization') {
      $this->voidPayment($staged_payment->getPayment());
    }
    $staged_payment->setState(StagedPaymentInterface::STATE_AUTHORIZATION_EXPIRED);
    $staged_payment->save();
  }

  /**
   * @inheritDoc
   */
  public function multiPaymentCapturePayment(StagedPaymentInterface $staged_payment) {
    $this->capturePayment($staged_payment->getPayment());
    $staged_payment->setState(StagedPaymentInterface::STATE_COMPLETED);
    $staged_payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();

    if (!empty($this->configuration['automatically_received'])) {
      $payment->setState('completed');
    }
    else {
      $payment->setState('pending');
    }
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * @inheritDoc
   */
  public function multiPaymentBuildForm(array $payment_form, FormStateInterface $form_state, array &$complete_form, OrderInterface $order) {
    $form = [];
    
    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => $this->t('Amount'),
      '#currency_code' => $order->getStore()->getDefaultCurrencyCode(),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#rows' => 3,
    ];
    
    return $form;
  }


}
