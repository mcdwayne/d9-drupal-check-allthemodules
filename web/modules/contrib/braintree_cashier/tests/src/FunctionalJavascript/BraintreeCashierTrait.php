<?php

namespace Drupal\Tests\braintree_cashier\FunctionalJavascript;

use Dotenv\Dotenv;
use Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlan;
use Drupal\braintree_cashier\Entity\BraintreeCashierSubscriptionInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\key\Entity\Key;

/**
 * The BraintreeCashierTrait to help with test classes.
 */
trait BraintreeCashierTrait {

  /**
   * Setup the Braintree API credentials.
   *
   * Credentials are read from the .env file.
   */
  protected function setupBraintreeApi() {
    $dotenv = new Dotenv(__DIR__ . '/../../');
    $dotenv->load();
    $public_key_entity = Key::create();
    $public_key_entity->setPlugin('key_provider', 'config');
    $public_key_entity->setPlugin('key_type', 'authentication');
    $public_key_entity->setPlugin('key_input', 'text_field');
    $public_key_entity->setKeyValue(getenv('BRAINTREE_SANDBOX_PUBLIC_KEY'));
    $public_key_entity->set('id', 'braintree_sandbox_public_key');
    $public_key_entity->set('label', 'Braintree Sandbox Public Key');
    $public_key_entity->save();

    $private_key_entity = Key::create();
    $private_key_entity->setPlugin('key_provider', 'config');
    $private_key_entity->setPlugin('key_type', 'authentication');
    $private_key_entity->setPlugin('key_input', 'text_field');
    $private_key_entity->setKeyValue(getenv('BRAINTREE_SANDBOX_PRIVATE_KEY'));
    $private_key_entity->set('id', 'braintree_sandbox_private_key');
    $private_key_entity->set('label', 'Braintree Sandbox Private Key');
    $private_key_entity->save();

    $this->config('braintree_api.settings')->initWithData([
      'environment' => 'sandbox',
      'sandbox_public_key' => 'braintree_sandbox_public_key',
      'sandbox_private_key' => 'braintree_sandbox_private_key',
      'sandbox_merchant_id' => getenv('BRAINTREE_SANDBOX_MERCHANT_ID'),
    ])->save();
  }

  /**
   * Creates the CI Monthly billing plan.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface
   *   The billing plan.
   */
  protected function createMonthlyBillingPlan() {
    $billing_plan = BraintreeCashierBillingPlan::create([
      'braintree_plan_id' => 'ci_monthly',
      'name' => 'CI Monthly',
      'subscription_type' => BraintreeCashierSubscriptionInterface::PAID_INDIVIDUAL,
      'is_available_for_purchase' => TRUE,
      'environment' => 'sandbox',
      'weight' => 0,
      'description' => 'CI Monthly for $12 / month',
      'long_description' => 'The is the best plan, so sign up now.',
      'call_to_action' => 'Sign up now',
      'price' => '$12',
    ]);
    $billing_plan->save();
    return $billing_plan;
  }

  /**
   * Creates the monthly billing plan with a free trial.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierBillingPlanInterface
   *   The billing plan.
   */
  protected function createMonthlyFreeTrialBillingPlan() {
    $billing_plan = BraintreeCashierBillingPlan::create([
      'braintree_plan_id' => 'monthly_free_trial',
      'name' => 'Monthly Free Trial',
      'subscription_type' => BraintreeCashierSubscriptionInterface::PAID_INDIVIDUAL,
      'is_available_for_purchase' => TRUE,
      'environment' => 'sandbox',
      'weight' => 0,
      'description' => 'Monthly Free Trial plan for $9 / month',
      'long_description' => 'A great plan, get it now.',
      'call_to_action' => 'Start Free Trial',
      'price' => '$9 / month',
      'has_free_trial' => TRUE,
    ]);
    $billing_plan->save();
    return $billing_plan;
  }

  /**
   * Creates a billing plan which will result in a processor declined error.
   *
   * @return \Drupal\Core\Entity\EntityInterface|static
   *   The billing plan.
   */
  protected function createProcessorDeclinedBillingPlan() {
    $billing_plan = BraintreeCashierBillingPlan::create([
      'braintree_plan_id' => 'processor_declined',
      'name' => 'CI Process Declined',
      'subscription_type' => BraintreeCashierSubscriptionInterface::PAID_INDIVIDUAL,
      'is_available_for_purchase' => TRUE,
      'environment' => 'sandbox',
      'weight' => 0,
      'description' => 'CI processor declined.',
      'long_description' => 'The is not the best plan.',
      'call_to_action' => 'Sign up now',
      'price' => '$2000.00',
    ]);
    $billing_plan->save();
    return $billing_plan;
  }

  /**
   * Fills in the Braintree credit card form in the Drop-in UI.
   *
   * This function presumes the Drop-in UI is embedded in the current page.
   *
   * @param \Drupal\FunctionalJavascriptTests\WebDriverTestBase $test
   *   The javascript test base object.
   * @param array $params
   *   The parameter array.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function fillInCardForm(WebDriverTestBase $test, array $params) {
    // Fill in credit card.
    $test->assertSession()->waitForElementVisible('css', '.braintree-loaded');
    $page = $test->getSession()->getPage();
    $card_option = $page->find('css', '.braintree-option__card');
    $test->assertNotEmpty($card_option);
    $card_option->click();

    $test->getSession()->switchToIFrame('braintree-hosted-field-number');
    $card_number = $test->getSession()->getPage()->find('css', '#credit-card-number');
    $test->assertTrue($card_number->isVisible());
    $test->getSession()->getPage()->fillField('credit-card-number', $params['card_number']);
    // Switch back to regular page.
    $test->getSession()->switchToIFrame(NULL);

    $test->getSession()->switchToIFrame('braintree-hosted-field-expirationDate');
    $test->getSession()->getPage()->fillField('expiration', $params['expiration']);
    $test->getSession()->switchToIFrame(NULL);

    $test->getSession()->switchToIFrame('braintree-hosted-field-cvv');
    $test->getSession()->getPage()->fillField('cvv', $params['cvv']);
    $test->getSession()->switchToIFrame(NULL);

    $test->getSession()->switchToIFrame('braintree-hosted-field-postalCode');
    $test->getSession()->getPage()->fillField('postal-code', $params['postal_code']);
    $test->getSession()->switchToIFrame(NULL);
  }

}
