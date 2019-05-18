<?php

namespace Drupal\drd\Crypt;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface for encryption methods.
 *
 * @ingroup drd
 */
interface BaseMethodInterface {

  /**
   * Whether the crypt method requires authentication before decryption.
   *
   * @return bool
   *   TRUE if authentication is required before decryption.
   */
  public function authBeforeDecrypt();

  /**
   * Can be overwritten and determines if the CryptMethod works with a password.
   *
   * @return bool
   *   TRUE if password is required.
   */
  public function requiresPassword();

  /**
   * Reset the crypt method password to force the generation of a new one.
   *
   * @return $this
   */
  public function resetPassword();

  /**
   * Get the crypt settings.
   *
   * @return array
   *   The settings.
   */
  public function getSettings();

  /**
   * Get the crypt method label.
   *
   * @return string
   *   The label.
   */
  public function getLabel();

  /**
   * Find out if the crypt method is available.
   *
   * @return bool
   *   TRUE if method is available.
   */
  public function isAvailable();

  /**
   * Get a list of available cipher methods.
   *
   * @return array
   *   List of methods.
   */
  public function getCipherMethods();

  /**
   * Add settings container into an existing form.
   *
   * @param array $form
   *   The form.
   * @param array $condition
   *   A list of conditions that can be used for visibility of components.
   */
  public function settingsForm(array &$form, array $condition);

  /**
   * Retrieve values from settings form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array
   *   The settings.
   */
  public function settingsFormValues(FormStateInterface $form_state);

  /**
   * Get an initialiation vector.
   *
   * @return string
   *   The IV.
   */
  public function getIv();

  /**
   * Get the selected cipher.
   *
   * @return string
   *   The cipher.
   */
  public function getCipher();

  /**
   * Get the password.
   *
   * @return string
   *   The password.
   */
  public function getPassword();

  /**
   * Encrypt and encode any list of arguments.
   *
   * @param array $args
   *   The arguments to be encrpyted.
   *
   * @return string
   *   Encrypted and base64 encoded serialisation of the arguments.
   */
  public function encrypt(array $args);

  /**
   * Decode, decrypt and unserialize arguments from the other end.
   *
   * @param string $body
   *   The encrypted, serialized and encoded string to process.
   * @param string $iv
   *   The initialiation vector.
   *
   * @return array
   *   The decoded, decrypted and unserialized arguments.
   */
  public function decrypt($body, $iv);

  /**
   * Encrypt a file.
   *
   * @param string $filename
   *   Filename which should be encrypted.
   *
   * @return string
   *   Filename of the encrypted version.
   */
  public function encryptFile($filename);

  /**
   * Decrypt a file.
   *
   * @param string $filename
   *   Filename which should be decrypted.
   *
   * @return string
   *   Filename of the decrypted version.
   */
  public function decryptFile($filename);

}
