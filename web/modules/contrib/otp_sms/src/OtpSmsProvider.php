<?php

namespace Drupal\otp_sms;

use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\user\UserDataInterface;
use Drupal\sms\Provider\PhoneNumberProviderInterface;
use Drupal\sms\Message\SmsMessage;

/**
 * The OTP SMS provider.
 */
class OtpSmsProvider implements OtpSmsProviderInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Phone number provider.
   *
   * @var \Drupal\sms\Provider\PhoneNumberProviderInterface
   */
  protected $phoneNumberProvider;

  /**
   * Constructs a new OtpSmsUserLoginFormAlter.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\sms\Provider\PhoneNumberProviderInterface $phone_number_provider
   *   The phone number provider.
   */
  public function __construct(RequestStack $request_stack, UserDataInterface $user_data, PhoneNumberProviderInterface $phone_number_provider) {
    $this->requestStack = $request_stack;
    $this->userData = $user_data;
    $this->phoneNumberProvider = $phone_number_provider;
  }

  /**
   * {@inheritdoc}
   */
  public function maybeSendNewCode(AccountInterface $user) {
    if ($this->shouldSendSmsToUser($user)) {
      $this->sendOtpSms($user);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function sendOtpSms(AccountInterface $user) {
    $current_time = $this->getCurrentTime();

    /** @var \Drupal\one_time_password\Plugin\Field\FieldType\ProvisioningUriItemList $otp_field */
    $otp_field = $user->one_time_password;
    $one_time_pass = $otp_field->getOneTimePassword();
    $code = $one_time_pass->at($current_time);

    $sms = (new SmsMessage())
      ->setMessage(sprintf('Your two factor code is %s', $code))
      ->setAutomated(FALSE);

    $this->phoneNumberProvider
      ->sendMessage($user, $sms);

    $this->setLastOtpSms($user);
  }

  /**
   * Get when a OTP SMS was last sent to a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   A user account.
   *
   * @return null|int
   *   The current time, or NULL if the user has never been sent an OTP SMS.
   */
  protected function getLastOtpSms(AccountInterface $user) {
    return $this->userData
      ->get('otp_sms', $user->id(), 'last_sms');
  }

  /**
   * Set when a OTP SMS was last sent to a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   A user account.
   * @param null|int $time
   *   The timestamp for when the OTP SMS was last sent, or NULL to use
   *   current time.
   */
  protected function setLastOtpSms(AccountInterface $user, $time = NULL) {
    $time = !isset($time) ? $this->getCurrentTime() : time();
    $this->userData
      ->set('otp_sms', $user->id(), 'last_sms', $time);
  }

  /**
   * How long to wait before sending a new SMS.
   *
   * @return int
   *   Number of seconds to wait before sending a new SMS.
   */
  protected function getOtpSmsLifetime() {
    return 3600;
  }

  /**
   * Determine whether a SMS can be sent to the user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   A user account.
   *
   * @return bool
   *   Whether a new OTP SMS should be sent to a user.
   */
  protected function shouldSendSmsToUser(AccountInterface $user) {
    $sms_lifetime = $this->getOtpSmsLifetime();
    $current_time = $this->getCurrentTime();
    $last_sms = $this->getLastOtpSms($user);
    if (!$last_sms) {
      // User has never been sent a SMS.
      return TRUE;
    }
    else {
      return ($current_time - $sms_lifetime) > $last_sms;
    }
  }

  /**
   * Get the current time.
   *
   * @return int
   *   The current time.
   */
  protected function getCurrentTime() {
    return $this->requestStack
      ->getCurrentRequest()
      ->server
      ->get('REQUEST_TIME');
  }

}
