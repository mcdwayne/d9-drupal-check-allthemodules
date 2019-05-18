<?php

namespace Drupal\okta_api\Service;

use Okta\Resource\User;

/**
 * Service class for User Profile.
 */
class Profile {

  /**
   * Okta Client.
   *
   * @var \Drupal\okta_api\Service\OktaClient
   */
  public $oktaClient;

  /**
   * Profile constructor.
   *
   * @param \Drupal\okta_api\Service\OktaClient $oktaClient
   *   Okta Client.
   */
  public function __construct(OktaClient $oktaClient) {
    $this->oktaClient = $oktaClient;
    $this->user = new User($oktaClient->Client);
    $this->oktaConfig = $oktaClient->config;
  }

  // @codingStandardsIgnoreStart
  //
  //  // TODO Extend the Profile
  //  //public function profileGet($something) {}
  //
  //  // TODO Extend the Profile
  //  public function profileSet($first_name, $last_name, $email_address, $user) {
  //    // TODO Extend the Profile, the code below needs refactoring.
  //    /*$this->profile = new UserProfile();
  //
  //    $this->profile->setFirstName($first_name)
  //      ->setLastName($last_name)
  //      ->setLogin($email_address)
  //      ->setEmail($email_address);
  //
  //    $user->setProfile($this->profile);
  //
  //    return $user;*/
  //  }
  // @codingStandardsIgnoreEnd

}
