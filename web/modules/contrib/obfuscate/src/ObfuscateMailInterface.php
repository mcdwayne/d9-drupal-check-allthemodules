<?php

namespace Drupal\obfuscate;

/**
 * Interface ObfuscateMailInterface.
 *
 * @package Drupal\obfuscate
 */
interface ObfuscateMailInterface {

  /**
   * ROT 13 class name of 'obfuscate'.
   *
   * This is not a dependency of ROT 13, it is merely a way
   * to remove hints for spammers.
   */
  const OBFUSCATE_CSS_CLASS = 'boshfpngr';

  /**
   * Returns an obfuscated link from an email address.
   *
   * @param string $email
   *   Email address.
   * @param array $params
   *   Optional parameters to be used by the a tag.
   *
   * @return array
   *   Obfuscated email link render array.
   */
  public function getObfuscatedLink($email, array $params = []);

  /**
   * Obfuscates an email address.
   *
   * @param string $email
   *   Email address.
   *
   * @return string
   *   Obfuscated email.
   */
  public function obfuscateEmail($email);

}
