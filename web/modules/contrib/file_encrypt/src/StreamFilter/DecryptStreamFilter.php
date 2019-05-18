<?php

namespace Drupal\file_encrypt\StreamFilter;

use Drupal\encrypt\Exception\EncryptException;

/**
 * Provides a stream filter for decryption.
 */
class DecryptStreamFilter extends StreamFilterBase {

  /**
   * The filter name, for use with stream filter functions.
   */
  const NAME = 'decrypt';

  /**
   * A buffer for storing data until a complete unit is reconstructed.
   *
   * @var string
   */
  protected $buffer = '';

  /**
   * The size of the next datum in bytes.
   *
   * @var int
   */
  protected $nextDatumSize = self::HEADER_LENGTH;

  /**
   * Whether or not the next datum is the header.
   *
   * @var bool
   */
  protected $datumIsHeader = TRUE;

  /**
   * {@inheritdoc}
   *
   * This buffers the data until it gets a decryptable datum and decrypts it.
   *
   * @see EncryptStreamFilter::filterData()
   */
  public function filter($in, $out, &$consumed, $closing) {
    while ($bucket = stream_bucket_make_writeable($in)) {

      // Move the bucket data onto the buffer.
      $this->buffer .= $bucket->data;
      $bucket->data = '';
      $consumed += $bucket->datalen;

      // Request more data if the buffer does not yet contain an atomic datum.
      if ($this->bufferLength() < $this->nextDatumSize) {
        return PSFS_FEED_ME;
      }

      while ($this->bufferLength() >= $this->nextDatumSize) {
        $datum = self::shiftDatumFromBuffer($this->buffer, $this->nextDatumSize);

        if (!$this->datumIsHeader) {
          try {
            $bucket->data .= $this->filterData($datum);
          }
          catch (EncryptException $e) {
            return PSFS_ERR_FATAL;
          }
          stream_bucket_append($out, $bucket);
        }

        $this->nextDatumSize = ($this->datumIsHeader) ? (int) rtrim($datum, self::HEADER_PADDING_CHARACTER) : self::HEADER_LENGTH;
        $this->datumIsHeader = !$this->datumIsHeader;
      }
    }
    return PSFS_PASS_ON;
  }

  /**
   * Returns the length of the current buffer in bytes.
   *
   * @return int
   *   The length of the current buffer in bytes.
   */
  protected function bufferLength() {
    return strlen($this->buffer);
  }

  /**
   * Removed the next datum from the front of the buffer and returns it.
   *
   * @return string
   *   The next datum from the buffer.
   */
  public static function shiftDatumFromBuffer(&$buffer, $next_datum_size) {
    $datum = mb_substr($buffer, 0, $next_datum_size);
    $buffer = mb_substr($buffer, $next_datum_size);
    return $datum;
  }

  /**
   * Filters data.
   *
   * @param string $data
   *   The data to filter.
   *
   * @return string
   *   The filtered data.
   */
  protected function filterData($data) {
    return $this->encryption->decrypt($data, $this->encryptionProfile);
  }

}
