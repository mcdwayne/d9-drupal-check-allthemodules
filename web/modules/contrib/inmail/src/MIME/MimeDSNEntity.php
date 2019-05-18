<?php

namespace Drupal\inmail\MIME;

/**
 * An abstraction of a Delivery Status Notification (DSN) MIME entity.
 *
 * The DSN is defined in RFC 3464. Its content type header field has the
 * following format:
 * @code
 * Content-Type: multipart/report; report-type=delivery-status
 * @endcode
 *
 * The second part of a DSN entity has content type "message/delivery-status".
 * Its body is comprised of groups of fields that have the same syntax as header
 * fields. The term "DSN field" is used here to denote such a field.
 *
 * @see http://tools.ietf.org/html/rfc3464
 *
 * @ingroup mime
 */
class MimeDSNEntity extends MimeMultipartMessage {

  /**
   * Groups of DSN fields.
   *
   * The first element contains per-message fields, and subsequent elements
   * contain per-recipient fields.
   *
   * Representation by the MimeHeader class is motivated by the fields following the
   * syntax of header fields.
   *
   * @var \Drupal\inmail\MIME\MimeHeader[]
   */
  protected $dsnFields;

  /**
   * Decorates a multipart entity into a DSN entity.
   *
   * @param \Drupal\inmail\MIME\MimeMultipartEntity $entity
   *   A multipart entity.
   * @param \Drupal\inmail\MIME\MimeHeader[] $dsn_fields
   *   A list of associative arrays, the first representing a per-message DSN
   *   field group, and each of the rest representing a per-recipient field
   *   group.
   */
  public function __construct(MimeMultipartEntity $entity, array $dsn_fields) {
    parent::__construct($entity, $entity->parts);
    $this->dsnFields = $dsn_fields;
  }

  /**
   * Returns the human-readable explanation part.
   *
   * @return \Drupal\inmail\MIME\MimeEntityInterface
   *   The first part of the DSN.
   */
  public function getHumanPart() {
    return $this->getPart(0);
  }

  /**
   * Returns the delivery-status part.
   *
   * @return \Drupal\inmail\MIME\MimeEntityInterface
   *   The second part of the DSN.
   */
  public function getStatusPart() {
    return $this->getPart(1);
  }

  /**
   * Returns the original message part.
   *
   * This part is optional so the method may return NULL. Additionally, note
   * that the message contained may be partial and not contain the entire
   * original message.
   *
   * @return \Drupal\inmail\MIME\MimeEntityInterface
   *   The third part of the DSN, if it is present.
   */
  public function getOriginalPart() {
    return $this->getPart(2);
  }

  /**
   * Returns the per-message DSN fields.
   *
   * This is the first group of fields in the delivery-status part. It is
   * required to contain at least a "Reporting-MTA" field.
   *
   * @return \Drupal\inmail\MIME\MimeHeader
   *   The per-message DSN fields.
   */
  public function getPerMessageFields() {
    return $this->dsnFields[0];
  }

  /**
   * Returns the indicated per-recipient DSN fields.
   *
   * The per-recipient field groups follow after the per-message field group.
   *
   * @param int $index
   *   The index of the field group to get. An $index of 0 refers to the first
   *   per-recipient field group.
   *
   * @return \Drupal\inmail\MIME\MimeHeader
   *   The field group at the given index, or NULL if the index is invalid.
   */
  public function getPerRecipientFields($index) {
    // Skip the first element, which is the per-message field group.
    $internal_index = $index + 1;
    return isset($this->dsnFields[$internal_index]) ? $this->dsnFields[$internal_index] : NULL;
  }

}
