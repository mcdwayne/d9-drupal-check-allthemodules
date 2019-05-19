<?php

namespace Drupal\stripe_examples\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Stripe\Charge;
use Stripe\Error\Base as StripeBaseException;
use Stripe\Stripe;

/**
 * Class SimpleCheckout.
 *
 * @package Drupal\stripe_examples\Form
 */
class SimpleCheckoutForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stripe_examples_simple_checkout';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $link_generator = \Drupal::service('link_generator');

    $form['first'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First name'),
    ];
    $form['last'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last name'),
    ];
    $form['stripe'] = [
      '#type' => 'stripe',
      '#title' => $this->t('Credit card'),
      // The selectors are gonna be looked within the enclosing form only.
      "#stripe_selectors" => [
        'first_name' => ':input[name="first"]',
        'last_name' => ':input[name="last"]',
      ],
      '#description' => $this->t('You can use test card numbers and tokens from @link.', [
        '@link' => $link_generator->generate('stripe docs', Url::fromUri('https://stripe.com/docs/testing')),
      ]),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    if ($this->checkTestStripeApiKey()) {
      $form['submit']['#value'] = $this->t('Pay $25');
    }

    $form['#attached']['library'][] = 'stripe_examples/stripe_examples';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->checkTestStripeApiKey()) {
      // Make test charge if we have test environment and api key.
      $stripe_token = $form_state->getValue('stripe');
      $charge = $this->createCharge($stripe_token, 25);
      if ($charge) {
        drupal_set_message('Charge status: ' . $charge->status);
        if ($charge->status == 'succeeded') {
          $link_generator = \Drupal::service('link_generator');
          drupal_set_message($this->t('Please check payments in @link.', [
            '@link' => $link_generator->generate('stripe dashboard', Url::fromUri('https://dashboard.stripe.com/test/payments')),
          ]));
        }
      }
    }
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }

  }

  /**
   * Helper function for making sure stripe key is set for test and has the necessary keys.
   */
  private function checkTestStripeApiKey() {
    $status = FALSE;
    $config = \Drupal::config('stripe.settings');
    if ($config->get('environment') == 'test' && $config->get('apikey.test.secret')) {
      $status = TRUE;
    }
    return $status;
  }

  /**
   * Helper function for test charge.
   *
   * @param string $stripe_token
   *   Stripe API token.
   * @param int $amount
   *   Amount for charge.
   *
   * @return /Stripe/Charge
   *   Charge object.
   */
  private function createCharge($stripe_token, $amount) {
    try {
      $config = \Drupal::config('stripe.settings');
      Stripe::setApiKey($config->get('apikey.test.secret'));
      $charge = Charge::create([
        'amount' => $amount * 100,
        'currency' => 'usd',
        'description' => "Example charge",
        'source' => $stripe_token,
      ]);
      return $charge;
    }
    catch (StripeBaseException $e) {
      drupal_set_message($this->t('Stripe error: %error', ['%error' => $e->getMessage()]), 'error');
    }
  }

}
