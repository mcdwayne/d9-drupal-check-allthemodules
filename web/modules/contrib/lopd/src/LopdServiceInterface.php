<?php

namespace Drupal\lopd;

/**
 * Interface LopdServiceInterface.
 *
 * @package Drupal\lopd
 */
interface LopdServiceInterface {

  const LOPD_OPERATION_LOGIN = 'login';
  const LOPD_OPERATION_LOGOUT = 'logout';
  const LOPD_OPERATION_LOGIN_FAILED = 'failed_login';

  /**
   * Registers the given $operation for the given $account.
   *
   * @param $account
   * @param type $operation
   *   The operation being registered.
   */
  function lopdRegisterOperation($account, $operation);

  /**
   * Registers an log in operation for the given $user.
   *
   * @param \Drupal\user\UserInterface $account
   */
  function lopdRegisterLogin($account);

  /**
   * Registers an log out operation for the given $user.
   *
   * @param \Drupal\user\UserInterface $account
   */
  function lopdRegisterLogout($account);

  /**
   * Registers an validation error operation for the given $user.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   */
  function lopdRegisterValidationAttempt($form_state);

  /**
   * Remove the lopd registries previous to date set at the configuration.
   */
  public function lopdDeleteRegisters();

}
