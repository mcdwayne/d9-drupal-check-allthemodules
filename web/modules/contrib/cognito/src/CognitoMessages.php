<?php

namespace Drupal\cognito;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * The cognito messages service.
 */
class CognitoMessages implements CognitoMessagesInterface {

  /**
   * The Cognito configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * CognitoMessages constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get('cognito.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function accountBlocked() {
    return $this->config->get('messages')['account_blocked'];
  }

  /**
   * {@inheritdoc}
   */
  public function passwordResetRequired() {
    return $this->config->get('messages')['password_reset_required'];
  }

  /**
   * {@inheritdoc}
   */
  public function registrationComplete() {
    return $this->config->get('messages')['registration_complete'];
  }

  /**
   * {@inheritdoc}
   */
  public function registrationConfirmed() {
    return $this->config->get('messages')['registration_confirmed'];
  }

  /**
   * {@inheritdoc}
   */
  public function confirmationResent() {
    return $this->config->get('messages')['attempt_confirmation_resend'];
  }

  /**
   * {@inheritdoc}
   */
  public function userAlreadyExistsRegister() {
    return $this->config->get('messages')['user_already_exists_register'];
  }

  /**
   * {@inheritdoc}
   */
  public function clickToConfirm() {
    return $this->config->get('messages')['click_to_confirm'];
  }

}
