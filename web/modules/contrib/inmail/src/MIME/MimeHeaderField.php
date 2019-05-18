<?php
/**
 * A value object for header field instances.
 *
 * @ingroup mime
 */

namespace Drupal\inmail\MIME;


class MimeHeaderField {

  /**
   * The name of the header field.
   *
   * @var string
   */
  protected $name;

  /**
   * The body of the header field.
   *
   * @var string
   */
  protected $body;

  /**
   * Constructs MimeHeaderField object.
   *
   * @param string $name
   *   Name of the header.
   * @param string $body
   *   Body of the header
   */
  public function __construct($name, $body) {
    $this->name = $name;
    $this->body = $body;
  }

  /**
   * Gets the name of the header field.
   *
   * @return string
   *   The header name.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Gets the body of the header field.
   *
   * @return string
   *   The body name.
   */
  public function getBody() {
    return $this->body;
  }

}
