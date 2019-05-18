<?php

namespace Drupal\drd\Crypt;

/**
 * Provides base encryption method.
 *
 * @ingroup drd
 */
abstract class BaseMethod implements BaseMethodInterface {

  /**
   * {@inheritdoc}
   */
  public function authBeforeDecrypt() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function requiresPassword() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function resetPassword() {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return array();
  }

  /**
   * Returns base64 encoded random bytes of the given length.
   *
   * @param int $length
   *   Length of the password to be generated.
   *
   * @return string
   *   Base64 encoded random password.
   */
  protected function generatePassword($length) {
    try {
      $randomBytes = random_bytes($length);
    }
    catch (\Exception $e) {
      drupal_set_message(t('Your system does not provide real good random data, hence you should fix that first before you continue with DRD!'), 'error');
      return '';
    }
    return base64_encode($randomBytes);
  }

  /**
   * Callback to encrypt and decrypt files.
   *
   * @param string $mode
   *   This is "-e" to encrypt or "-d" to decrypt.
   * @param string $in
   *   Input filename.
   * @param string $out
   *   Output filename.
   *
   * @return int
   *   Exit code of the openssl command.
   */
  private function cryptFileExecute($mode, $in, $out) {
    $output = array();
    $cmd = array(
      'openssl',
      $this->getCipher(),
      $mode,
      '-a',
      '-salt',
      '-in',
      $in,
      '-out',
      $out,
      '-k',
      base64_encode($this->getPassword()),
    );
    exec(implode(' ', $cmd), $output, $ret);
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function encryptFile($filename) {
    if ($this->getCipher()) {
      exec('openssl version', $output, $ret);
      if ($ret == 0) {
        $in = $filename;
        $filename .= '.openssl';
        if ($this->cryptFileExecute('-e', $in, $filename) != 0) {
          $filename = $in;
        }
      }
    }
    return $filename;
  }

  /**
   * {@inheritdoc}
   */
  public function decryptFile($filename) {
    if (pathinfo($filename, PATHINFO_EXTENSION) == 'openssl') {
      if ($this->getCipher()) {
        exec('openssl version', $output, $ret);
        if ($ret == 0) {
          $in = $filename;
          $filename = substr($in, 0, -8);
          if ($this->cryptFileExecute('-d', $in, $filename) != 0) {
            $filename = $in;
          }
        }
      }
    }
    return $filename;
  }

}
