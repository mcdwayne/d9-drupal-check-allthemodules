<?php

namespace Drupal\file_encrypt\StreamFilter;

use Drupal\encrypt\EncryptionProfileInterface;
use Drupal\encrypt\EncryptServiceInterface;

/**
 * Provides a base class for stream filters.
 */
abstract class StreamFilterBase extends \php_user_filter {

  /**
   * The byte length reserved for the data header.
   *
   * @see maxPayloadLength()
   */
  const HEADER_LENGTH = 7;

  /**
   * The character to use to pad the data header.
   */
  const HEADER_PADDING_CHARACTER = "\0";

  /**
   * The encryption service.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface
   */
  protected $encryption;

  /**
   * The encryption profile.
   *
   * @var \Drupal\encrypt\EncryptionProfileInterface
   */
  protected $encryptionProfile;

  /**
   * {@inheritdoc}
   */
  public function onCreate() {
    $required_params = [
      'encryption_service' => EncryptServiceInterface::class,
      'encryption_profile' => EncryptionProfileInterface::class,
    ];
    foreach ($required_params as $name => $type) {
      assert(isset($this->params[$name]) && $this->params[$name] instanceof $type, sprintf("Missing or invalid '%s' parameter.", $name, $type));
    }
    $this->encryption = $this->params['encryption_service'];
    $this->encryptionProfile = $this->params['encryption_profile'];
  }

  /**
   * Returns the data payload maximum length in bytes.
   *
   * @return int
   *   The maximum data payload length in bytes.
   */
  public static function maxPayloadLength() {
    return (int) str_repeat(9, self::HEADER_LENGTH);
  }

}
