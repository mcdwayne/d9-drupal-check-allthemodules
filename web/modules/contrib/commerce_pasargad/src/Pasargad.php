<?php

namespace Drupal\commerce_pasargad;

class Pasargad implements PasargadInterface {

  /**
   * {@inheritdoc}
   */
  public static function sign(array $data, $private_key) {
    $plain_text = '#';
    foreach ($data as $value) {
      $plain_text .= $value . '#';
    }

    $rsa = new \phpseclib\Crypt\RSA;
    $rsa->loadKey($private_key);
    $rsa->setSignatureMode(\phpseclib\Crypt\RSA::SIGNATURE_PKCS1);
    $sign = $rsa->sign($plain_text);
    $sign = base64_encode($sign);
    return $sign;
  }

  /**
   * {@inheritdoc}
   */
  public static function post($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
  }
}