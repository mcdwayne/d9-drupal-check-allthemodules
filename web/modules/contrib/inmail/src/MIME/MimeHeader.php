<?php

namespace Drupal\inmail\MIME;

use Drupal\Component\Utility\Unicode;

/**
 * An abstraction of an email header.
 *
 * The header is defined in RFC 5322. In short, it consists of fields of the
 * form "Name: body", separated by newlines.
 *
 * Newlines may also occur within field bodies, but then only followed by space.
 * This allows fields to break across multiple lines, facilitating adherence to
 * the maximal line length of 78 recommended by the RFC.
 *
 * A header is allowed to have multiple fields with the same name, but the
 * accessors in this class only support finding the first occurrence (if any).
 *
 * @see http://tools.ietf.org/html/rfc5322#section-2.2
 * @see http://tools.ietf.org/html/rfc5322#section-3.6
 *
 * @ingroup mime
 */
class MimeHeader {

  /**
   * The fields constituting the header.
   *
   * @var \Drupal\inmail\MIME\MimeHeaderField[]
   */
  protected $fields = [];

  /**
   * The raw original header from parsing.
   *
   * @var string
   */
  protected $raw;

  /**
   * Creates a new MimeHeader object containing the optionally given fields.
   *
   * @param \Drupal\inmail\MIME\MimeHeaderField[] $fields
   *   A list of fields, represented by arrays with string elements for the keys
   *   'name' and 'body'.
   * @param string $raw
   *   (optional) raw value, default value is NULL.
   */
  public function __construct($fields = array() , $raw = NULL) {
    foreach ($fields as $field) {
      if (!empty($field->getName()) && !empty($field->getBody())) {
        $this->addField($field, FALSE);
      }
    }

    $this->raw = $raw;
  }

  /**
   * Returns the body of the first field with the given name.
   *
   * Some field names are allowed by the standards to occur more than once. This
   * accessor is however designed to only return the first occurrence (newest,
   * if fields are added to the top).
   *
   * @param string $name
   *   The name of a header field.
   *
   * @return null|string
   *   The body of the field or NULL if the field is not present.
   */
  public function getFieldBody($name) {
    $key = $this->findFirstField($name);
    if ($key === FALSE) {
      return NULL;
    }
    return trim($this->fields[$key]->getBody());
  }

  /**
   * Checks if there is a field.
   *
   * @param string $name
   *   The name of the field to find.
   *
   * @return bool
   *   Return TRUE if there is a field, or FALSE if no field
   */
  public function hasField($name) {
    $field = $this->findFirstField($name);
    return ($field !== FALSE);
  }

  /**
   * Adds a field to the header.
   *
   * Note that in the context of an MTA processing a message, headers are
   * usually added to the beginning rather than the end. It is up to the caller
   * of this method to ensure that added fields conform to standards as desired.
   *
   * @param \Drupal\inmail\MIME\MimeHeaderField $field
   *   The Field
   * @param bool $prepend
   *   If TRUE, the header is added to the beginning of the header, otherwise it
   *   is added to the end. Defaults to TRUE.
   */
  public function addField(MimeHeaderField $field, $prepend = TRUE) {
    if (!empty($field->getName()) && !empty($field->getBody())) {
      if ($prepend) {
        array_unshift($this->fields, $field);
      }
      else {
        $this->fields[] = $field;
      }
    }
  }

  /**
   * Removes the first field with the given name.
   *
   * @param string $name
   *   The name of a field to remove. If no field with that name is present,
   *   nothing happens.
   *
   * @see MimeHeader::getFieldbody()
   */
  public function removeField($name) {
    $key = $this->findFirstField($name);
    if ($key !== FALSE) {
      array_splice($this->fields, $key, 1);
    }
  }

  /**
   * Returns the index of the first field with the given name.
   *
   * Fields are iterated from top to bottom.
   *
   * @param string $name
   *   The name of the field to find.
   *
   * @return int|bool
   *   The index of the field in the internal field list, or FALSE if no field
   *   with that name is present.
   */
  protected function findFirstField($name) {
    // Iterate through headers and find the first match.
    foreach ($this->fields as $key => $field) {
      // Field name is case-insensitive.
      if (strcasecmp($field->getName(), $name) == 0) {
        return $key;
      }
    }
    return FALSE;
  }

  /**
   * Returns an associative array of header fields.
   *
   * @return \Drupal\inmail\MIME\MimeHeaderField[]
   *   An associative array of header fields.
   */
  public function getFields() {
    return $this->fields;
  }

  /**
   * Concatenates the fields to a header string.
   *
   * Fields and bodies are newly encoded based on the values.
   * This could result in different representation than parsed.
   * Raw header values are ignored.
   *
   * @return string
   *   The header as a string, terminated by a newline.
   */
  public function toString() {
    $header = array();
    foreach ($this->fields as $field) {
      // Encode non-7bit body. If body is 7bit, mimeHeaderEncode() does nothing.
      $body = static::mimeHeaderEncode($field->getBody(), strlen($field->getName()));
      $encoded = $body != $field->getBody();

      $field_string = "{$field->getName()}: $body";
      // Fold to match 78 char length limit, and append. The encoding includes
      // folding, so only do it for unencoded body. The \h matches whitespace
      // except newline.
      // @todo Prefer breaking at "higher-level syntactic breaks" like ";"
      $field_string = !$encoded ? preg_replace('/(.{0,78})(\h|$)/', "\\1\n\\2", $field_string) : $field_string;
      $header[] = trim($field_string);
    }
    return implode("\n", $header);
  }

  /**
   * Modification of Unicode::mimeHeaderEncode().
   *
   * This version respects the header field name when calculating the line
   * length limit.
   *
   * @see \Drupal\Component\Utility\Unicode::mimeHeaderEncode()
   *
   * @todo Remove if this is fixed in core, https://www.drupal.org/node/2407117
   *
   * @param string $string
   *   The field body to encode.
   * @param int $field_name_length
   *   (optional) Length of the name of the field whose body is to be encoded.
   *
   * @return string
   *   The MIME-encoded field body.
   */
  public static function mimeHeaderEncode($string, $field_name_length = 0) {
    if (preg_match('/[^\x20-\x7E]/', $string)) {
      $chunk_size_full = 47; // floor((75 - strlen("=?UTF-8?B??=")) * 0.75);
      // Adapt chunk size to field name.
      $chunk_size = max(0, $chunk_size_full - $field_name_length);
      $len = strlen($string);
      $output = '';
      while ($len > 0) {
        $chunk = Unicode::truncateBytes($string, $chunk_size);
        $output .= ' =?UTF-8?B?' . base64_encode($chunk) . "?=\n";
        $c = strlen($chunk);
        $string = substr($string, $c);
        $len -= $c;
        // For subsequent folding, ignore field name length.
        $chunk_size = $chunk_size_full;
      }
      return trim($output);
    }
    return $string;
  }

  /**
   * Returns the body of the fields with the given name.
   *
   * @param string $name
   *   The name of a header field.
   * @param bool $filter
   *   Whether '(comments)' should be filtered from the content.
   *
   * @return string
   *   The body of the field or empty array if the field is not present.
   */
  public function getFieldBodies($name, $filter = FALSE) {
    // Iterate through headers and find the matches.
    $body = [];
    foreach ($this->fields as $key => $field) {
      if (strcasecmp($field->getName(), $name) == 0) {
        $body[] = trim($this->fields[$key]->getBody());
      }
    }
    return ($filter && $body) ? preg_replace('/\([^)]*\)/', '', $body) : $body;
  }

  /**
   * Returns the raw header value.
   *
   * @return string
   */
  public function getRaw() {
    return $this->raw;
  }

}
