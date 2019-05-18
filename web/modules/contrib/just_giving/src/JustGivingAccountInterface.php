<?php

namespace Drupal\just_giving;

/**
 * Interface JustGivingAccountInterface.
 */
interface JustGivingAccountInterface {

  /**
   * @param array $jgAccountDetails
   *
   * @return mixed
   */
  public function setJgAddressDetails(array $jgAccountDetails);

  /**
   * @param array $jgAccountDetails
   *
   * @return mixed
   */
  public function setJgAccountDetails(array $jgAccountDetails);

  /**
   * @param $user_email
   *
   * @return mixed
   */
  public function checkAccountExists(string $user_email);

  /**
   * @param $email
   * @param $password
   *
   * @return mixed
   */
  public function validateAccount($email, $password);

  /**
   * @return mixed
   */
  public function createAccount();

}
