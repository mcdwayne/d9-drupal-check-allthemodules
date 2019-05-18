<?php

namespace Drupal\commerce_epayco;

use Epayco\Epayco;

/**
 * Class ePayco.
 *
 * @package Drupal\commerce_epayco
 */
class CommerceEPayco {

  /**
   * Contruct method.
   *
   * @param string $apiKey
   *   API key provided by ePayco.
   * @param string $privateKey
   *   Private key provided by ePayco.
   * @param string $language
   *   Language code. Ex.: "en", "es".
   * @param bool $test
   *   TRUE or FALSE, depending if operations will be made in test mode.
   */
  public function __construct($apiKey, $privateKey, $language, $test) {
    $this->client = new Epayco([
      'apiKey' => $apiKey,
      'privateKey' => $privateKey,
      'lenguage' => $language,
      'test' => $test,
    ]);
  }

  /**
   * Method to create the credit card token.
   *
   * @param string $card_number
   *   Credit card's number.
   * @param int $card_exp_year
   *   Expire year.
   * @param string $card_exp_month
   *   Expire month.
   * @param string $card_cvc
   *   Security code.
   */
  public function createCardToken($card_number, $card_exp_year, $card_exp_month, $card_cvc) {
    $token = $this->client->token->create([
      'card[number]' => $card_number,
      'card[exp_year]' => $card_exp_year,
      'card[exp_month]' => $card_exp_month,
      'card[cvc]' => $card_cvc,
    ]);

    return $token;
  }

  /**
   * Save customer information into ePayco account.
   *
   * @param string $token_card
   *   Token card, as returned by $this->createCardToken.
   * @param string $name
   *   Customer's name.
   * @param string $email
   *   Customer's email.
   * @param string $phone
   *   Customer's phone.
   * @param bool $default
   *   TRUE or FALSE, if default.
   */
  public function createCustomer($token_card, $name, $email, $phone, $default) {
    $customer = $this->client->customer->create([
      'token_card' => $token_card,
      'name' => $name,
      'email' => $email,
      'phone' => $phone,
      'default' => $default,
    ]);

    return $customer;
  }

  /**
   * Get customer information from ePayco.
   *
   * @param string $id_customer
   *   The customer ID in ePayco.
   */
  public function getCustomer($id_customer) {
    $customer = $this->client->customer->get($id_customer);

    return $customer;
  }

  /**
   * Update customer information in ePayco.
   *
   * @param string $id_customer
   *   ID of the customer to be updated.
   * @param array $data
   *   Data to be updated. See $this->createCustomer to see available keys.
   */
  public function updateCustomer($id_customer, array $data = []) {
    $customer = $this->client->customer->update($id_customer, $data);

    return $customer;
  }

  /**
   * Get a list of saved customers in ePayco.
   */
  public function getCustomers() {
    $customers = $this->client->customer->getList();

    return $customers;
  }

  /**
   * Create an ePayco payment plan.
   *
   * @param string $id_plan
   *   Unique plan identifier.
   * @param string $name
   *   Human readable name for this plan.
   * @param string $description
   *   Human readable description for this plan.
   * @param numeric $amount
   *   Value to be paid for this plan.
   * @param string $currency
   *   Currency code. Example: COP.
   * @param string $interval
   *   Plan payment interval. Example: "month", "week", "day".
   * @param int $interval_count
   *   Means every $interval_count $interval, when this plan must be paid.
   * @param int $trial_days
   *   If you will offer some free days for this plan.
   */
  public function createPlan($id_plan, $name, $description, numeric $amount, $currency, $interval, $interval_count, $trial_days) {
    $plan = $this->client->plan->create([
      'id_plan' => $id_plan,
      'name' => $name,
      'description' => $description,
      'amount' => $amount,
      'currency' => $currency,
      'interval' => $interval,
      'interval_count' => $interval_count,
      'trial_days' => $trial_days,
    ]);

    return $plan;
  }

  /**
   * Get information about a Plan.
   *
   * @param string $id_plan
   *   Unique identifier per plan.
   */
  public function getPlan($id_plan) {
    $plan = $this->client->plan->get($id_plan);

    return $plan;
  }

  /**
   * Delete a Plan.
   *
   * @param string $id_plan
   *   Plan identifier to be deleted.
   */
  public function removePlan($id_plan) {
    $plan = $this->client->plan->remove($id_plan);

    return $plan;
  }

  /**
   * Get a list of available plans.
   */
  public function getPlans() {
    $plans = $this->client->plan->getList();

    return $plans;
  }

  /**
   * Create a new subscription.
   */
  public function createSubscription($id_plan, $customer, $token_card, $doc_type, $doc_number) {
    $subscription = $this->client->subscriptions->create([
      'id_plan' => $id_plan,
      'customer' => $customer,
      'token_card' => $token_card,
      'doc_type' => $doc_type,
      'doc_number' => $doc_number,
    ]);

    return $subscription;
  }

  /**
   * Cancel a subscription.
   */
  public function removeSubscription($id_client) {
    $subscription = $this->client->subscriptions->cancel($id_client);

    return $subscription;
  }

  /**
   * Get a list of subscriptions.
   */
  public function getSubscriptions() {
    $subscriptions = $this->client->subscriptions->getList();

    return $subscriptions;
  }

  /**
   * Charge subscription.
   */
  public function chargeSubscription($id_plan, $id_client, $token_card, $url_response, $url_confirmation, $doc_type, $doc_number) {
    $subscription = $this->client->subscriptions->charge([
      'id_plan' => $id_plan,
      'customer' => $id_client,
      'token_card' => $token_card,
      'url_response' => $url_response,
      'url_confirmation' => $url_confirmation,
      'doc_type' => $doc_type,
      'doc_number' => $doc_number,
    ]);

    return $subscription;
  }

}
