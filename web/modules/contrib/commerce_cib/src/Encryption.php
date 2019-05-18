<?php

namespace Drupal\commerce_cib;

/**
 * Manages encryption for CIB API requests.
 */
class Encryption implements EncryptionInterface {

  /**
   * The contents of the keyfile.
   *
   * @var string
   */
  protected $keyfile;

  /**
   * The 3 3DES encryption keys concatenated.
   *
   * @var string
   */
  protected $key;

  /**
   * The initialization vector.
   *
   * @var string
   */
  protected $iv;

  /**
   * {@inheritdoc}
   */
  public function encrypt($plaintext) {
    $arr = explode('&', $plaintext);
    $outs = "";
    $pid = "";
    foreach ($arr as $pm) {
      if (strtoupper($pm) != 'CRYPTO=1') {
        $outs .= '&' . $pm;
      }
      if (substr(strtoupper($pm), 0, 4) == 'PID=') {
        $pid=substr(strtoupper($pm), 4, 7);
      }
    }
    $outs = substr($outs, 1);

    $crc = str_pad(dechex(crc32($outs)), 8, "0", STR_PAD_LEFT);
    for ($i = 0; $i < 4; $i++) {
      $outs .= chr(base_convert(substr($crc, $i * 2, 2), 16, 10));
    }
    $outs = openssl_encrypt($outs, 'des-ede3-cbc', $this->key, OPENSSL_RAW_DATA, $this->iv);

    $pad = 3 - (strlen($outs) % 3);
    for ($i = 0; $i < $pad; $i++) {
      $outs .= chr($pad);
    }
    $outs = base64_encode($outs);
    $outs = rawurlencode($outs);
    $outs = "PID=" . $pid . "&CRYPTO=1&DATA=" . $outs;
    return $outs;
  }

  /**
   * {@inheritdoc}
   */
  public function setKeyfile($path) {
    if ($path) {
      $f = fopen($path, "r");
      if ($f) {
        $keyinfo = fread($f, 38);
        fclose($f);
        $k1 = substr($keyinfo, 14, 8);
        $k2 = substr($keyinfo, 22, 8);
        $this->setIv(substr($keyinfo, 30, 8));
        $this->setKey($k1 . $k2 . $k1);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setKey($key) {
    $this->key = $key;
  }

  /**
   * {@inheritdoc}
   */
  public function setIv($iv) {
    $this->iv = $iv;
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($encrypted_message) {
    $arr = explode("&", $encrypted_message);
    $outs = '';
    for ($i=0; $i < count($arr); $i++) {
      if (substr(strtoupper($arr[$i]), 0, 5) == 'DATA=') {
        $outs = substr($arr[$i], 5);
      }
    }
    $outs = rawurldecode($outs);
    $outs = base64_decode($outs);
    $lastc = ord($outs[strlen($outs) - 1]);
    $validpad = 1;
    for ($i = 0; $i < $lastc; $i++) {
      if (ord(substr($outs, strlen($outs) - 1 - $i, 1)) != $lastc) {
        $validpad = 0;
      }
    }
    if ($validpad==1) {
      $outs = substr($outs, 0, strlen($outs) - $lastc);
    }
    $outs = openssl_decrypt($outs, 'DES-EDE3-CBC', $this->key, OPENSSL_RAW_DATA, $this->iv);

    $lastc = ord($outs[strlen($outs) - 1]);
    $validpad = 1;
    for ($i = 0; $i < $lastc; $i++) {
      if (ord(substr($outs, strlen($outs) - 1 - $i, 1)) != $lastc) {
        $validpad = 0;
      }
    }
    if ($validpad == 1) {
      $outs = substr($outs, 0, strlen($outs) - $lastc);
    }
    $crc = substr($outs, strlen($outs) - 4);
    $crch = "";
    for ($i = 0; $i < 4; $i++) {
      $crch .= str_pad(dechex(ord($crc[$i])), 2, "0", STR_PAD_LEFT);
    }
    $outs = substr($outs, 0, strlen($outs) - 4);
    $crc = str_pad(dechex(crc32($outs)), 8, "0", STR_PAD_LEFT);
    if ($crch != $crc) {
      return "";
    }
    $outs = str_replace("&", "%26", $outs);
    $outs = str_replace("=", "%3D", $outs);
    $outs = utf8_encode(rawurldecode($outs));
    $outs = "CRYPTO=1&" . $outs;
    return $outs;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * {@inheritdoc}
   */
  public function getIv() {
    return $this->iv;
  }

}
