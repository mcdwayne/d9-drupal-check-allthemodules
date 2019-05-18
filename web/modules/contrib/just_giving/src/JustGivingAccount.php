<?php

namespace Drupal\just_giving;

use Drupal\just_giving\JustGivingClient;

/**
 * Class JustGivingAccount.
 */
class JustGivingAccount implements JustGivingAccountInterface {

  protected $justGivingClient;

  protected $justGivingAddress;

  protected $jgAddressDetails;

  protected $justGivingAccRequest;

  protected $jgAccountDetails;

  /**
   * Constructs a new JustGivingAccount object.
   *
   * @param \Drupal\just_giving\JustGivingClientInterface $just_giving_client
   */
  public function __construct(JustGivingClientInterface $just_giving_client) {
    $this->justGivingClient = $just_giving_client;
  }

  /**
   * @param array $jgAddressDetails
   *
   * @return mixed|void
   */
  public function setJgAddressDetails(array $jgAddressDetails) {
    $this->jgAddressDetails = $jgAddressDetails;
  }

  /**
   * @param array $jgAccountDetails
   *
   * @return mixed|void
   */
  public function setJgAccountDetails(array $jgAccountDetails) {
    $this->jgAccountDetails = $jgAccountDetails;
  }

  /**
   * @return mixed
   */
  public function createAccount() {
    $jg_account_request = $this->createAccountRequest();
    return $this->justGivingClient->jgLoad()->Account->create($jg_account_request);
  }

  /**
   * @param $user_email
   *
   * @return mixed
   */
  public function checkAccountExists(string $user_email) {
    return $this->justGivingClient->jgLoad()->Account->IsEmailRegistered($user_email);
  }

  /**
   * @param $email
   * @param $password
   *
   * @return mixed
   */
  public function validateAccount($email, $password) {
    $credentials = [
      'email' => $email,
      'password' => $password,
    ];
    return $this->justGivingClient->jgLoad()->Account->IsValid($credentials);
  }

  /**
   * @param $email
   *
   * @return mixed
   */
  public function passwordReminder($email) {
    $reminderResult = $this->justGivingClient->jgLoad()->Account->RequestPasswordReminder($email);
    if (isset($reminderResult['0']->id) && $reminderResult['0']->id == "AccountNotFound") {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * @param $email
   * @param $password
   *
   * @return mixed
   */
  public function retrieveAccount($email, $password) {
    $this->justGivingClient->setUsername($email);
    $this->justGivingClient->setPassword($password);
    return $this->justGivingClient->jgLoad()->Account->AccountDetails();
  }

  /**
   * @param $form_state
   */
  public function signupUser($form_state) {

    $jgAddressDetails = [
      'line1' => $form_state->getValue('first_line_of_address'),
      'line2' => $form_state->getValue('second_line_of_address'),
      'town_or_city' => $form_state->getValue('town_or_city'),
      'county_or_state' => $form_state->getValue('county_or_state'),
      'country' => $form_state->getValue('country'),
      'postcode_or_zipcode' => $form_state->getValue('postcode'),
    ];
    $jgAccountDetails = [
      'reference' => $form_state->getValue('reference'),
      'title' => $form_state->getValue('title'),
      'first_name' => $form_state->getValue('first_name'),
      'last_name' => $form_state->getValue('last_name'),
      'email' => $form_state->getValue('email'),
      'password' => $form_state->getValue('password'),
      'accept' => $form_state->getValue('accept_terms_and_conditions'),
    ];

    $this->setJgAddressDetails($jgAddressDetails);
    $this->setJgAccountDetails($jgAccountDetails);
    return $this->createAccount();
  }

  /**
   * @return \CreateAccountRequest
   */
  private function createAccountRequest() {

    $this->justGivingAccRequest = new \CreateAccountRequest();
    $this->justGivingAccRequest->reference = $this->jgAccountDetails['reference'];
    $this->justGivingAccRequest->title = $this->jgAccountDetails['title'];
    $this->justGivingAccRequest->firstName = $this->jgAccountDetails['first_name'];
    $this->justGivingAccRequest->lastName = $this->jgAccountDetails['last_name'];
    $this->justGivingAccRequest->email = $this->jgAccountDetails['email'];
    $this->justGivingAccRequest->password = $this->jgAccountDetails['password'];
    $this->justGivingAccRequest->acceptTermsAndConditions = $this->jgAccountDetails['accept'];
    $this->justGivingAccRequest->address = $this->buildAddress();

    return $this->justGivingAccRequest;
  }

  /**
   * @return \Address
   */
  private function buildAddress() {

    $this->justGivingAddress = new \Address();
    $this->justGivingAddress->line1 = $this->jgAddressDetails['line1'];
    $this->justGivingAddress->line2 = $this->jgAddressDetails['line2'];
    $this->justGivingAddress->townOrCity = $this->jgAddressDetails['town_or_city'];
    $this->justGivingAddress->countyOrState = $this->jgAddressDetails['county_or_state'];
    $this->justGivingAddress->country = $this->jgAddressDetails['country'];
    $this->justGivingAddress->postcodeOrZipcode = $this->jgAddressDetails['postcode_or_zipcode'];

    return $this->justGivingAddress;
  }

}
