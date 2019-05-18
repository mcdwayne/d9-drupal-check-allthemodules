<?php

namespace Drupal\file_encrypt\StreamFilter;

use Drupal\encrypt\Exception\EncryptException;

/**
 * Provides a stream filter for encryption.
 */
class EncryptStreamFilter extends StreamFilterBase {

  /**
   * The filter name, for use with stream filter functions.
   */
  const NAME = 'encrypt';

  /**
   * {@inheritdoc}
   */
  public function filter($in, $out, &$consumed, $closing) {
    while ($bucket = stream_bucket_make_writeable($in)) {
      try {
        $bucket->data = $this->filterData($bucket->data);
      }
      catch (EncryptException $e) {
        return PSFS_ERR_FATAL;
      }
      $consumed += $bucket->datalen;
      stream_bucket_append($out, $bucket);
    }
    return PSFS_PASS_ON;
  }

  /**
   * Filters data.
   *
   * This encrypts the data and prepends a padded, fixed-length header
   * containing the size of the payload in bytes so that the decrypt filter
   * knows how much data to buffer before attempting decryption (i.e., so the
   * decrypt filter knows how large the succeeding decryptable datum is).
   *
   * @param string $data
   *   The data to filter.
   *
   * @return string
   *   The filtered data.
   *
   * @see DecryptStreamFilter::filter()
   */
  public function filterData($data) {
    $payload = $this->encryption->encrypt($data, $this->encryptionProfile);
    $this->validatePayload($payload);
    $header = $this->buildHeader($payload);
    return $header . $payload;
  }

  /**
   * Validates the payload.
   *
   * @param string $payload
   *   The payload.
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   *   If the payload is invalid.
   */
  protected function validatePayload($payload) {
    $length = strlen($payload);
    $max_length = self::maxPayloadLength();
    if ($length > $max_length) {
      throw new EncryptException(sprintf('Payload is too large--%s bytes of maximum %s.', number_format($length), number_format($max_length)));
    }
  }

  /**
   * Builds the data header given the payload.
   *
   * @param string $payload
   *   The data payload.
   *
   * @return string
   *   The data header.
   */
  protected function buildHeader($payload) {
    $length = strlen($payload);
    return str_pad($length, self::HEADER_LENGTH, self::HEADER_PADDING_CHARACTER);
  }

}
