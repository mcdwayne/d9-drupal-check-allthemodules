<?php

namespace Drupal\paybox;

/**
 * Class CheckSignin.
 *
 * @package Drupal\paybox
 *
 * A simple service to check if signature
 * of the Paybox server's response URL is correct.
 */
class CheckSignin {

  /**
   * Check user sign.
   *
   * @param string $query_string
   *   The query string.
   *
   * @return bool
   *   TRUE if signing is correct, FALSE otherwise.
   */
  public function checkUserSign($query_string) {
    $matches = [];
    if (preg_match('/(?:q=.*?&)?(.*)&sig=(.*)$/', $query_string, $matches)) {
      $data = $matches[1];
      $sig = base64_decode(urldecode($matches[2]));

      $key_file = drupal_get_path('module', 'paybox') . '/pubkey.pem';
      if ($key_file_content = file_get_contents($key_file)) {
        if ($key = openssl_pkey_get_public($key_file_content)) {
          return openssl_verify($data, $sig, $key);
        }
      }
      \Drupal::logger('paybox')->notice(
        'Cannot read Paybox System public key file (@file)',
        ['@file' => $key_file]
      );
    }
    return FALSE;
  }

}
