<?php

namespace Drupal\tfa_ga_totp;

/**
 * Main class.
 */
class GaOtp {
  /**
   * The digits the code can have.
   *
   * Either 6 or 8.
   * Authenticator does only support 6.
   *
   * @var int
   */
  private $digits = 6;

  /**
   * Time in seconds one counter period is long.
   *
   * @var int
   */
  private $period = 30;

  /**
   * Time in seconds for skew.
   *
   * @var varchar
   */
  public $timeSkew;

  /**
   * Currently used algorithm.
   *
   * @var string
   */
  private $algorithm = 'sha1';

  /**
   * Time offset between system time and GMT in seconds.
   *
   * @var int
   */
  private $totpOffset = 0;

  /**
   * Construct for settings.
   */
  public function __construct() {
    $plugin_settings = \Drupal::config('tfa.settings')->get('validation_plugin_settings');
    $settings = isset($plugin_settings['tfa_totp']) ? $plugin_settings['tfa_totp'] : [tfa_totp];
    $settings = array_replace([
      'time_skew' => 30,
      'site_name_prefix' => TRUE,
      'name_prefix' => 'TFA',
      'issuer' => 'Drupal',
    ], $settings);
    $this->timeSkew = $settings['time_skew'];
  }

  /**
   * Get timer count.
   */
  private function getTimecounter() {
    return floor((time() + $this->totpOffset) / $this->period);
  }

  /**
   * Check tfa time drift.
   */
  public function gaCheckTotp($secret, $key, $timedrift = 1) {
    if (!is_numeric($timedrift) || $timedrift < 0) {
      throw new \InvalidArgumentException('Invalid timedrift supplied');
    }
    // Counter comes from time now
    // Also we check the current timestamp as well as previous and future ones
    // according to $timerange.
    $timecounter = $this->getTimecounter();

    // We first try the current, as it is the most likely to work.
    if (hash_equals($this->totp($secret, $timecounter), $key)) {
      return TRUE;
    }
    elseif ($timedrift == 0) {
      // When timedrift is 0, this is the end of the checks.
      return FALSE;
    }

    return FALSE;
  }

  /**
   * Get timer count.
   */
  private function totp($secret, $timecounter = NULL) {
    if (is_null($timecounter)) {
      $timecounter = $this->getTimecounter();
    }
    return $this->hotp($secret, $timecounter);
  }

  /**
   * Hashing counter.
   */
  private function hotp($secret, $counter) {
    if (!is_numeric($counter) || $counter < 0) {
      throw new \InvalidArgumentException('Invalid counter supplied');
    }

    $hash = hash_hmac(
              $this->algorithm,
              $this->getBinaryCounter($counter),
              $secret,
              TRUE
    );

    return str_pad($this->truncate($hash), $this->digits, '0', STR_PAD_LEFT);
  }

  /**
   * Get binary counter.
   */
  private function getBinaryCounter($counter) {
    // On 64 bit, PHP >= 5.6.3 this is "2038 safe".
    if (8 === PHP_INT_SIZE && PHP_VERSION_ID >= 50603) {
      return pack('J', $counter);
    }

    // Keep old behavior for 32 bit PHP or PHP < 5.6.3.
    return pack('N*', 0) . pack('N*', $counter);
  }

  /**
   * Truncate function.
   */
  private function truncate($hash) {
    $offset = ord($hash[19]) & 0xf;

    return (
          ((ord($hash[$offset + 0]) & 0x7f) << 24) |
          ((ord($hash[$offset + 1]) & 0xff) << 16) |
          ((ord($hash[$offset + 2]) & 0xff) << 8) |
          (ord($hash[$offset + 3]) & 0xff)
          ) % pow(10, $this->digits);
  }

}
