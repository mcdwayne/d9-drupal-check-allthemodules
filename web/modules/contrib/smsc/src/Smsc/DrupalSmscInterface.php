<?php

/**
 * @file
 */

namespace Drupal\smsc\Smsc;

/**
 * Interface DrupalSmscInterface
 *
 * @package Drupal\smsc\Smsc
 */
interface DrupalSmscInterface {

  /**
   * Get settings.
   *
   * @return null|\Smsc\Settings\Settings
   */
  public function getSettings();

  /**
   * Get available sender ID's.
   *
   * @return array
   */
  public static function senders();

  /**
   * Send SMS.
   *
   * @param string $phones
   * @param string $message
   * @param array  $options
   *
   * @return mixed
   */
  public static function sendSms($phones, $message, $options = []);

  /**
   * Get SMSC config.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   */
  public function getConfig();

  /**
   * Get available hosts.
   *
   * @return array
   */
  public function getHosts();

  /**
   * Get available sender ID's.
   *
   * @return null|array
   */
  public function getSenders();
}
