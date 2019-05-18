<?php

/**
 * Sample scrambler created as an example of AES plugin.
 */

namespace Drupal\aes\Plugin\AES;

use Drupal\aes\Plugin\AESPluginBase;

/**
 * Sample cryptor plugin. Does not really encrypt a string but just doing minor scrambling.
 *
 * Created only for demonstration purposes, does not support binary data, multibyte text, etc.
 *
 * @Cryptor(
 *   id = "aes_encrypt_reverse",
 *   label = "AES reverse 'encryption'",
 *   description = "Sample AES encryption plugin.",
 * )
 *
 * @package Drupal\aes\Plugin\AES
 */
class Reverse extends AESPluginBase {
  const SIGNATURE = 'Consider this an encrypted string: ';

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct([], 'aes-reverse', []);
  }

  /**
   * Reverse the string.
   *
   * {@inheritdoc}
   */
  public function encrypt($data, $key = FALSE, $cipher = FALSE) {
    return self::SIGNATURE . strrev($data);
  }

  /**
   * Recover previously scrambled string.
   *
   * {@inheritdoc}
   */
  public function decrypt($data, $key = FALSE, $cipher = FALSE) {
    if (strpos($data, self::SIGNATURE) !== 0) {
      throw new \Exception("Sorry, this is not mine: " . $data);
    }
    return strrev(substr($data, strlen(self::SIGNATURE)));
  }

}
