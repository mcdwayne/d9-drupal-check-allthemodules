<?php

namespace Drupal\contact_emails\Entity;

use Drupal\contact\MessageInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Contact Email entity.
 *
 * @ingroup contact_emails
 */
interface ContactEmailInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Get the email subject.
   *
   * @param MessageInterface $message
   *   The contact message.
   *
   * @return string
   *   The subject of the email.
   */
  public function getSubject(MessageInterface $message);

  /**
   * Get the email body.
   *
   * @param MessageInterface $message
   *   The contact message.
   *
   * @return string
   *   The body of the email.
   */
  public function getBody(MessageInterface $message);

  /**
   * Get the email body format.
   *
   * @param MessageInterface $message
   *   The contact message.
   *
   * @return string
   *   The email body format.
   */
  public function getFormat(MessageInterface $message);

  /**
   * Get the email recipient(s).
   *
   * @param MessageInterface $message
   *   The contact message.
   *
   * @return array
   *   The recipient(s) of the email.
   */
  public function getRecipients(MessageInterface $message);

  /**
   * Get the reply-to email address.
   *
   * @param MessageInterface $message
   *   The contact message.
   *
   * @return string
   *   The reply-to email address.
   */
  public function getReplyTo(MessageInterface $message);

}
