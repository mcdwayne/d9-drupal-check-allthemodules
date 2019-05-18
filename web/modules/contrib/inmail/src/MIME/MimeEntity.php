<?php

namespace Drupal\inmail\MIME;

use Drupal\Component\Utility\Unicode;

/**
 * A MIME entity is typically an email message or a part of a multipart message.
 *
 * @ingroup mime
 */
class MimeEntity implements MimeEntityInterface {

  /**
   * The entity header.
   *
   * @var \Drupal\inmail\MIME\MimeHeader
   */
  protected $header;

  /**
   * The entity body in 7-bit ASCII.
   *
   * @var string
   */
  protected $body;

  /**
   * Constructs a new Entity.
   *
   * @param \Drupal\inmail\MIME\MimeHeader $header
   *   The entity header.
   * @param string $body
   *   The entity body. The charset and any encoding of the body must be 7-bit
   *   ASCII and match the Content-Transfer-Encoding and Content-Type fields in
   *   the given header.
   */
  public function __construct(MimeHeader $header, $body) {
    $this->header = $header;
    $this->body = $body;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader() {
    return $this->header;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentType() {
    $field = $this->getHeader()->getFieldBody('Content-Type');
    if (empty($field)) {
      // Default content type defined in RFC 2045 sec 5.2.
      $field = 'text/plain; charset=us-ascii';
    }
    $field_parts = preg_split('/\s*;\s*/', $field);

    list($type, $subtype) = explode('/', array_shift($field_parts));

    $parameters = array();
    foreach ($field_parts as $part) {
      list($attribute, $value) = preg_split('/\s*=\s*/', $part, 2);
      // Trim surrounding quotes.
      $parameters[strtolower($attribute)] = trim($value, '"');
    }

    return array(
      'type' => $type,
      'subtype' => $subtype,
      'parameters' => $parameters,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    if ($this->header->hasField('Content-Disposition')) {
      $disposition_field = $this->header->getFieldBody('Content-Disposition');
      $field_parts = preg_split('/\s*;\s*/', $disposition_field, 2);
      return reset($field_parts);
    }

    return $this->getContentType()['type'] . '/' . $this->getContentType()['subtype'];
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTransferEncoding() {
    $field = $this->getHeader()->getFieldBody('Content-Transfer-Encoding');
    if (empty($field)) {
      // Default encoding defined in RFC 2045 sec 6.1.
      $field = '7bit';
    }
    return $field;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * {@inheritdoc}
   */
  public function getDecodedBody() {
    $body = $this->getBody();

    // Decode base64/quoted-printable.
    $body = MimeEncodings::decode($body, $this->getContentTransferEncoding());
    if ($body === NULL || $body === FALSE) {
      // Unrecognized encoding.
      return NULL;
    }

    // Convert to UTF-8.
    $content_type = $this->getContentType();
    // Default to US-ASCII.
    $charset = isset($content_type['parameters']['charset']) ? $content_type['parameters']['charset'] : 'us-ascii';
    if (in_array(strtolower($charset), ['utf-8', 'us-ascii'])) {
      // No need to convert, but validate UTF-8.
      return Unicode::validateUtf8($body) ? $body : NULL;
    }
    // convertToUtf8 may return FALSE.
    $body = Unicode::convertToUtf8($body, $charset);
    if ($body === FALSE) {
      return NULL;
    }
    // Return decoded, converted, valid UTF-8 body.
    return $body;
  }

  /**
   * {@inheritdoc}
   */
  public function toString() {
    // A blank line terminates the header section and begins the body.
    return $this->getHeader()->toString() . "\n\n" . $this->getBody();
  }

}
