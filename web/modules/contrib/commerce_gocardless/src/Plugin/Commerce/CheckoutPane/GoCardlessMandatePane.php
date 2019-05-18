<?php

namespace Drupal\commerce_gocardless\Plugin\Commerce\CheckoutPane;

use Drupal\commerce\Response\NeedsRedirectException;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\profile\Entity\ProfileInterface;
use GoCardlessPro\Core\Exception\GoCardlessProException;

/**
 * Examines the order's payment method and redirect to GoCardless if required.
 *
 * The user will be redirected if both of the following are true:
 *   - the payment method is of type "commerce_gocardless_oneoff"
 *   - there is no mandate set in the payment method's "Remote ID" property.
 *
 * If no GoCardless intervention is required, this pane does nothing.
 *
 * This pane should be added to the "review" step, and have a lower weight
 * than the "review" pane.
 *
 * @CommerceCheckoutPane(
 *   id = "commerce_gocardless_mandate",
 *   label = @Translation("GoCardless mandate"),
 *   default_step = "review",
 * )
 */
class GoCardlessMandatePane extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    // A payment gateway must be set before this pane is used.
    if ($this->order->get('payment_gateway')->isEmpty()) {
      drupal_set_message($this->t('No payment gateway selected.'), 'error');
      $this->redirectToPreviousStep();
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->order->payment_method->entity;

    // Do nothing if this order does not involve GoCardless.
    if (empty($payment_method) || $payment_method->bundle() !== 'commerce_gocardless_oneoff') {
      return $pane_form;
    }

    // Do nothing if this order is using an existing mandate.
    if (!empty($payment_method->getRemoteId())) {
      return $pane_form;
    }

    /** @var \Drupal\commerce_gocardless\Plugin\Commerce\PaymentGateway\GoCardlessPaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->order->payment_gateway->entity->getPlugin();

    /** @var \GoCardlessPro\Client $client */
    $client = $payment_gateway_plugin->createGoCardlessClient();
    $session_token = \Drupal::request()->getSession()->getId();

    // We want to return to MandateConfirmationController, which will
    // take care of updating the payment method and redirect back to the
    // checkout flow.
    $success_redirect_url = Url::fromRoute('commerce_gocardless.mandate_confirmation', [
      'commerce_order' => $this->order->id(),
    ], ['absolute' => TRUE]);

    $params['description'] = $payment_gateway_plugin->getDescription();
    $params['session_token'] = $session_token;
    $params['success_redirect_url'] = $success_redirect_url->toString();

    // Prefill the GoCardless form as much as we can.
    if ($profile = $payment_method->getBillingProfile()) {
      $email = $this->order->getEmail();
      $params['prefilled_customer'] = $this->formatProfileAsGoCardlessCustomer($profile, $email);
    }

    // Redirect the user to GC at this point.
    try {
      $redirectFlow = $client->redirectFlows()->create(['params' => $params]);
      throw new NeedsRedirectException($redirectFlow->redirect_url);

    }
    catch (GoCardlessProException $e) {
      \Drupal::logger('commerce_gocardless')->error($e->getMessage());

      // If this happens we are left with a payment method that doesn't
      // have a mandate ID set, and cannot be used. To avoid it showing up
      // in the list of possible existing payment methods, delete it.
      $this->order->set('payment_method', NULL);
      $this->order->save();
      $payment_method->delete();

      drupal_set_message($this->t('Unable to create a new direct debit with GoCardless.'), 'error');
      $this->redirectToPreviousStep();
    }
  }

  /**
   * Redirects to a previous checkout step on error.
   *
   * @throws \Drupal\commerce\Response\NeedsRedirectException
   */
  protected function redirectToPreviousStep() {
    $url = Url::fromRoute('commerce_checkout.form', [
      'commerce_order' => $this->order->id(),
      'step' => $this->checkoutFlow->getPreviousStepId($this->getStepId()),
    ], ['absolute' => TRUE]);
    throw new NeedsRedirectException($url->toString());
  }

  /**
   * Takes a profile object and email address, and returns an array suitable
   * for use as the 'prefilled_customer' property in the redirect flow request.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   * @param $email
   *
   * @return array
   */
  private function formatProfileAsGoCardlessCustomer(ProfileInterface $profile, $email) {
    $customer = [
      'given_name' => $profile->address->given_name,
      'family_name' => $profile->address->family_name,
      'company_name' => $profile->address->organisation,
      'address_line1' => $profile->address->address_line1,
      'address_line2' => $profile->address->address_line2,
      'postal_code' => $profile->address->postal_code,
      'city' => $profile->address->locality,
      'region' => $profile->address->administrative_area,
      'country_code' => $profile->address->country_code,
      'email' => $email,
    ];
    return array_map(function ($str) {
      // Send '' instead of NULL.
      return $str ? $str : '';
    }, $customer);
  }

}
