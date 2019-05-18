<?php

namespace Drupal\inmail\MIME;

/**
 * Provides methods for a MIME Entity.
 *
 * @ingroup mime
 */
interface MimeEntityInterface {

  /**
   * Returns the header of the entity.
   *
   * Note: The term "header" is sometimes, more or less informally, applied to a
   * single entry in the actual header. Those entries are correctly named
   * "fields".
   *
   * @return \Drupal\inmail\MIME\MimeHeader
   *   The header.
   */
  public function getHeader();

  /**
   * Returns the content type of the entity.
   *
   * The body of the Content-Type header field contains "type/subtype" possibly
   * followed by a set of parameters.
   *
   * Example:
   * @code
   * Content-Type: text/plain; charset=utf-8
   * @endcode
   *
   * If the field is omitted, default values are assumed as defined by RFC 2045.
   * The default type is text/plain with parameters charset=us-ascii.
   *
   * @return array
   *   The returned array contains three elements:
   *     - type: The general part of the MIME type (before '/')
   *     - subtype: The specific part of the MIME type (after '/')
   *     - parameter: The parameters as an associative array, possibly empty.
   *       Keys are in lower-case.
   */
  public function getContentType();

  /**
   * Returns the message part type. (inline, attachment, plain, html)
   *
   * @return string
   *   The type.
   */
  public function getType();

  /**
   * Returns the encoding of the body.
   *
   * @return string
   *   The encoding as specified by the Content-Transfer-Encoding header field,
   *   or the default "7bit" if that field is omitted.
   */
  public function getContentTransferEncoding();

  /**
   * Returns the body.
   *
   * The body may be encoded as indicated by the Content-Type and
   * Content-Transfer-Encoding header fields. Use ::getDecodedBody() to access
   * the payload.
   *
   * @return string
   *   The entity body.
   */
  public function getBody();

  /**
   * Decodes and returns the body.
   *
   * @return string|null
   *   The entity body as a normal UTF-8 string, or NULL if decoding failed.
   */
  public function getDecodedBody();

  /**
   * Joins the header with the body to produce a string.
   *
   * A blank line terminates the header section and begins the body.
   *
   * @return string
   *   A string representation of the entity.
   */
  public function toString();

}
