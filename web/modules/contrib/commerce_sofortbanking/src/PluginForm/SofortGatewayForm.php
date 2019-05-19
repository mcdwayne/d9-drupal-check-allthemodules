<?php

namespace Drupal\commerce_sofortbanking\PluginForm;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the offsite payment form for the SOFORT payment gateway.
 */
class SofortGatewayForm extends PaymentOffsiteForm {

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_sofortbanking\Plugin\Commerce\PaymentGateway\SofortGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;

    $sofort = $payment_gateway_plugin->initializeSofortApi($payment, $form);
    $sofort->sendRequest();

    if ($sofort->isError()) {
      // SOFORT-API didn't accept the data.
      throw new PaymentGatewayException(sprintf('SOFORT error: %s', $sofort->getError()));
    }

    $transaction_id = $sofort->getTransactionId();
    $payment->setRemoteId($transaction_id);
    $payment->state = 'authorization';
    $payment->save();
    $order = $payment->getOrder();
    $order->setData('sofort_gateway', [
      'transaction_id' => $transaction_id,
    ]);
    $order->save();

    return $this->buildRedirectForm($form, $form_state, $sofort->getPaymentUrl(), []);
  }

}
