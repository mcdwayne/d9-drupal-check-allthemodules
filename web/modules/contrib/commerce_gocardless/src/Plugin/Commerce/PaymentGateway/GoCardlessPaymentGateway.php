<?php

namespace Drupal\commerce_gocardless\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use GoCardlessPro\Client;
use GoCardlessPro\Core\Exception\GoCardlessProException;

/**
 * GoCardless payment gateway.
 *
 * This is an on-site gateway as it operates on the basis of there already
 * being a mandate.
 *
 * @CommercePaymentGateway(
 *   id = "gocardless",
 *   label = "GoCardless",
 *   display_label = "GoCardless",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_gocardless\PluginForm\GoCardlessPaymentMethodAddForm",
 *   },
 *   modes = {
 *     "sandbox" = "Sandbox",
 *     "live" = "Live",
 *   },
 *   payment_method_types = {"commerce_gocardless_oneoff"},
 * )
 */
class GoCardlessPaymentGateway extends OnsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'description' => '',
      'access_token' => '',
      'webhook_secret' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('This will be visible on the GoCardless site and identifies your organisation.'),
      '#default_value' => $this->configuration['description'],
      '#required' => TRUE,
    ];
    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access token'),
      '#description' => $this->t("The API token supplied by GoCardless."),
      '#default_value' => $this->configuration['access_token'],
      '#required' => TRUE,
    ];
    $form['webhook_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook secret'),
      '#description' => $this->t("An arbitrary string which GoCardless will use to verify itself when making API requests to this site."),
      '#default_value' => $this->configuration['webhook_secret'],
      '#required' => FALSE,  // you need to get this from GC
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
      $this->configuration['description'] = $values['description'];
      $this->configuration['access_token'] = $values['access_token'];
      $this->configuration['webhook_secret'] = $values['webhook_secret'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);

    // The stored payment method will have the mandate ID, which is
    // what we pass to GoCardless to identify the buyer.
    $mandate_id = $payment_method->getRemoteId();
    if (!$mandate_id) {
      throw new HardDeclineException('No direct debit mandate was set up with GoCardless.');
    }

    // Perform the create payment request here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    // Remember to take into account $capture when performing the request.
    //    $amount = $payment->getAmount();

    // Create a payment on GoCardless.
    // The payment won't be approved immediately, so
    try {
      $gc_payment_id = $this->createGoCardlessPayment($payment, $mandate_id);
    }
    catch (GoCardlessProException $e) {
      throw new PaymentGatewayException('GoGardless exception: ' . $e->getMessage());
    }

    // We need to be able to identify this payment later, so store the GC id.
    $payment->setRemoteId($gc_payment_id);

    // Update the payment status to pending_capture - GoCardless will call the
    // webhook once it can be confirmed.
    $payment->setState('pending_capture');
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    // Note that we don't have a mandate ID at this stage, but we do still
    // want to have a saved payment method in order to progress through
    // checkout.
    // The payment method is updated with the mandate ID in
    // MandateConfirmationController.
    $payment_method->setReusable(TRUE);
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // Delete the remote record here, throw an exception if it fails.
    // See \Drupal\commerce_payment\Exception for the available exceptions.
    // Delete the local entity.
    $payment_method->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function createGoCardlessClient() {
    if (!isset($this->configuration['mode']) || !isset($this->configuration['access_token'])) {
      throw new \Exception('Unable to create GoCardless client because the payment gateway configuration does not specify a mode (environment) and access token.');
    }

    return new Client([
      'environment' => $this->configuration['mode'],
      'access_token' => $this->configuration['access_token'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return isset($this->configuration['description']) ? $this->configuration['description'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getWebhookSecret() {
    return isset($this->configuration['webhook_secret']) ? $this->configuration['webhook_secret'] : '';
  }

  /**
   * Create the payment in GoCardless.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment entity.
   * @param $mandate_id
   *   The mandate ID.
   *
   * @return
   *   The ID of the GoCardless payment.
   */
  private function createGoCardlessPayment(PaymentInterface $payment, $mandate_id) {
    /** @var \Drupal\commerce_price\RounderInterface $rounder */
    $rounder = \Drupal::service('commerce_price.rounder');

    $amount = $payment->getAmount();
    $this->assertCurrencyGBP($amount);
    $amount_in_pounds = $rounder->round($payment->getAmount())->getNumber();
    $amount_in_pence = (int) Calculator::multiply($amount_in_pounds, 100);

    // A unique identifier to guard against multiple payment creation requests
    // being made for the same real-world payment.
    // see https://developer.gocardless.com/api-reference/#making-requests-idempotency-keys
    $idempotency_key = 'payment-for-order-' . $payment->getOrder()->id();

    $gc_payment = $this->createGoCardlessClient()->payments()->create([
      "params" => [
        "amount" => $amount_in_pence,
        "currency" => $amount->getCurrencyCode(),
        "links" => [
          "mandate" => $mandate_id,
        ],
      ],
      "headers" => [
        "Idempotency-Key" => $idempotency_key,
      ],
    ]);

    return $gc_payment->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getMandateDescription(PaymentMethodInterface $payment_method) {
    if ($payment_method->getRemoteId()) {
      $client = $this->createGoCardlessClient();

      try {
        $mandate = $client->mandates()->get($payment_method->getRemoteId());
      }
      catch (GoCardlessProException $e) {
        return $this->t('Invalid debit mandate');
      }

      $bank_account_ref = $mandate->links->customer_bank_account;
      if ($bank_account_ref) {
        $bank_account = $client->customerBankAccounts()->get($bank_account_ref);
        return $this->t('@account_holder_name, @bank_name, account number ending @account_number_ending', [
          '@account_holder_name' => $bank_account->account_holder_name,
          '@bank_name' => $bank_account->bank_name,
          '@account_number_ending' => $bank_account->account_number_ending,
        ]);
      }
    }
    return '';
  }

  /**
   * Asserts that the payment amount currency is GBP.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the price is not in GBP.
   */
  protected function assertCurrencyGBP(Price $price) {
    if ($price->getCurrencyCode() !== 'GBP') {
      throw new \InvalidArgumentException('The payment amount must be in GBP.');
    }
  }

}
