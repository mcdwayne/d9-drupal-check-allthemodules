<?php

namespace Drupal\otp_sms;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface for OtpSmsProvider.
 */
interface OtpSmsProviderInterface {

  /**
   * Determine whether to send new sms.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to send a OTP SMS.
   *
   * @return bool
   *   Whether a new OTP SMS was sent.
   */
  public function maybeSendNewCode(AccountInterface $user);

  /**
   * Send a OTP SMS to a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   Send a OTP SMS to this user.
   */
  public function sendOtpSms(AccountInterface $user);

}
