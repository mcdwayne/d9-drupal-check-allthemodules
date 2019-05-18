<?php

namespace Drupal\extra_tokens\ZinaDesign;

class PasswordResetTokenGenerator {

  const PASSWORD_RESET_TIMEOUT_DAYS = 14;

  const KEY_SALT = "django.contrib.auth.tokens.PasswordResetTokenGenerator";

  /**
   * Check that a password reset token is correct for a given user.
   *
   * @param $user
   * @param $token
   *
   * @return bool
   */
  public static function check_token($user, $token) {
    if (!($user && $token)) {
      return FALSE;
    }
    # Parse the token
    $p = explode('-', $token);
    if (count($p) !== 2) {
      return FALSE;
    }
    list($ts_b36, $hash) = $p;
    $ts = self::base36_to_int($ts_b36);

    # Check that the timestamp/uid has not been tampered with
    if (self::_make_token_with_timestamp($user, $ts) !== $token) {
      return FALSE;
    }
    # Check the timestamp is within limit
    if ((self::_num_days(new \DateTime('now')) - $ts) > self::PASSWORD_RESET_TIMEOUT_DAYS) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Converts an integer to a base36 string
   *
   * @param int $i
   *
   * @return mixed
   */
  public static function int_to_base36($i) {
    return base_convert($i, 10, 36);
  }

  public static function base36_to_int($s) {
    return base_convert($s, 36, 10);
  }

  public static function salted_hmac($key_salt, $value, $secret = NULL) {
    if (is_null($secret)) {
      $secret = '2!*^*2%#i#9cd_(m12qs6gn0y@k((gxzr5%2r=d1exk^5=8bju';
    }
    $key = sha1($key_salt . $secret);
    return hash_hmac("sha1", $value, $key);
  }

  private static function _make_hash_value($user, $timestamp) {

    if (!$user->last_login) {
      $login_timestamp = '';
    }
    else {
      $login_timestamp = $user->last_login;
    }
    return ((string) $user->id . (string) $user->password . $login_timestamp . $timestamp);
  }

  /**
   * @param \DateTime $dt
   *
   * @return mixed
   */
  private static function _num_days($dt) {
    $from = new \DateTime('2001-01-01');
    return $dt->diff($from)->days;
  }

  private static function _make_token_with_timestamp($user, $timestamp) {
    $ts_b36 = self::int_to_base36($timestamp);
    $key_salt = "django.contrib.auth.tokens.PasswordResetTokenGenerator";
    $hash = self::salted_hmac($key_salt,
      self::_make_hash_value($user, $timestamp));
    $token = '';
    for ($i = 0; $i < strlen($hash); $i++) {
      $ch = $hash[$i];
      if ($i % 2 === 0) {
        continue;
      }
      $token .= $ch;
    }
    return "{$ts_b36}-{$token}";
  }

  public static function make_token($user) {
    return self::_make_token_with_timestamp($user,
      self::_num_days(new \DateTime('now')));
  }

  public static function urlsafe_b64encode($string) {
    $data = base64_encode($string);
    $data = str_replace(['+', '/', '='], ['-', '_', ''], $data);
    return $data;
  }

  public static function urlsafe_b64decode($string) {
    $data = str_replace(['-', '_'], ['+', '/'], $string);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
      $data .= substr('====', $mod4);
    }
    return base64_decode($data);
  }
}