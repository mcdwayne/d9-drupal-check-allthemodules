<?php

namespace Drupal\inmail\MIME;

/**
 * Utility class for decoding and encoding in the context of MIME.
 *
 * @ingroup mime
 */
class MimeEncodings {

  /**
   * Decode a string according to a MIME encoding type.
   *
   * The encoding types are defined in
   * https://tools.ietf.org/html/rfc2045#section-6.1
   *
   * @param string $body
   *   Encoded string to decode.
   * @param string $encoding
   *   The encoding of the string.
   *
   * @return string|null|false
   *   The decoded string. If the encoding type was not recognized, NULL is
   *   returned. If the decoding failed, FALSE is returned.
   */
  public static function decode($body, $encoding) {
    switch ($encoding) {
      case 'base64':
        return base64_decode($body);

      case 'quoted-printable':
        return quoted_printable_decode($body);

      // All these are statements about the domain of the data, rather than
      // references to types of encoding.
      case '7bit':
      case '8bit':
      case 'binary':
        return $body;

      // Unrecognized encoding.
      default:
        return NULL;
    }
  }

}
