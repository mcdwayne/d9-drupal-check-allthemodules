<?php

namespace Drupal\inmail\MIME;

/**
 * Provides methods for a MIME email message.
 *
 * @ingroup mime
 */
interface MimeMessageInterface extends MimeEntityInterface {

  /**
   * Returns the Message-Id.
   *
   * The RFC declares that the Message-Id field "should" be set, but it is not
   * required. The value has the format "<id-left@id-right>"
   *
   * @see http://tools.ietf.org/html/rfc5322#section-3.6.4
   *
   * @return string|null
   *   The body of the Message-Id field, or NULL if it is not set.
   */
  public function getMessageId();

  /**
   * Returns the References field.
   *
   * The RFC declares the References field "should" occur in some replies,
   * but it is not required. May be used to identify a thread of conversation.
   *
   * Value, one of the following (depending on the available parent's fields):
   *   - parent's References field body (if any) followed by parent's msg-id
   *   - parent's In-Reply-To field body followed by parent's mgs-id (if any)
   *   - none (if parent has no References, In-Reply-To or msg-id fields)
   * Note: Each identifier are separated by a white space.
   *
   * @see http://tools.ietf.org/html/rfc5322#section-3.6.4
   *
   * @return string[]|null
   *   Array of Message-ID's, or NULL if References field is not set.
   */
  public function getReferences();

  /**
   * Returns the In-Reply-To field.
   *
   * The RFC declares the In-Reply-To field "should" occur in some replies,
   * but it is not required. May be used to identify the message to which the
   * new message is a reply.
   *
   * Value, one of the following (depending on the available parent's fields):
   *   - parent's msg-id
   *   - all parent's msg-id (if there is more than one parent message)
   *   - none (if any of the parent message has no msg-id)
   * Note: The identifier are separated by a white space.
   * According to RFC, In-Reply-To could have multiple parent's msg-id,
   * even though many real mail client examples provide just one identifier.
   * Here we will prevent this special case handling multiple parent's msg-id.
   *
   * @see http://tools.ietf.org/html/rfc5322#section-3.6.4
   *
   * @return string[]|null
   *   Array of Message-ID's, or NULL if In-Reply-To field is not set.
   */
  public function getInReplyTo();

  /**
   * Returns the message subject.
   *
   * @return string|null
   *   The content of the 'Subject' header field, or null if that field does
   *   not exist.
   */
  public function getSubject();

  /**
   * Returns the message sender.
   *
   * @return \Drupal\inmail\MIME\Rfc2822Address[]|null
   *   List of 'From' recipient address objects.
   */
  public function getFrom();

  /**
   * Returns the unique message identifier(s).
   *
   * @return \Drupal\inmail\MIME\Rfc2822Address|\Drupal\inmail\MIME\Rfc2822Address[]
   *   The 'References'/'In-Reply-To' header field address object(s), NULL if
   *   it does not exist or it is the same as 'From'.
   */
  public function getReplyTo();

  /**
   * Returns the list of message recipients.
   *
   * @return \Drupal\inmail\MIME\Rfc2822Address[]
   *   List of 'To' recipient address objects.
   */
  public function getTo();

  /**
   * Returns the list of Cc recipients.
   *
   * @return \Drupal\inmail\MIME\Rfc2822Address[]
   *   List of 'Cc' recipient address objects.
   */
  public function getCc();

  /**
   * Returns the list of Bcc recipients.
   *
   * @return \Drupal\inmail\MIME\Rfc2822Address[]
   *   List of 'Bcc' recipient address objects.
   */
  public function getBcc();

  /**
   * Returns the date when the message was received by the recipient.
   *
   * @return \Drupal\Component\Datetime\DateTimePlus|null
   *   The received date from the header or null if not found.
   */
  public function getReceivedDate();

  /**
   * Extracts plaintext representation of body.
   *
   * This method is no longer used for the mail display.
   * Use \Drupal\inmail\MIME\MimeMessageDecompositionInterface::getBodyPaths()
   * and its "plain" output instead.
   *
   * @return string
   *   Resulting plain texts of body, otherwise empty string.
   */
  public function getPlainText();

  /**
   * Extracts HTML body representation.
   *
   * This method is no longer used for the mail display.
   * Use \Drupal\inmail\MIME\MimeMessageDecompositionInterface::getBodyPaths()
   * and its "html" output instead.
   *
   * @return string
   *   Resulting string contains HTML markup for the message body.
   */
  public function getHtml();

  /**
   * Returns the date when the message was sent.
   *
   * @return string
   *   The content of the 'Date' header field.
   */
  public function getDate();

}
