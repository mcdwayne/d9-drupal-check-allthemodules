<?php

namespace Drupal\inmail\MIME;

/**
 * A multipart message.
 *
 * This is the combination of \Drupal\collect\MIME\MimeMultipartEntity and
 * \Drupal\collect\MIME\MimeMessage.
 */
class MimeMultipartMessage extends MimeMultipartEntity implements MimeMessageInterface {

  use MimeMessageTrait;

  /**
   * {@inheritdoc}
   */
  public function getMessageId() {
    return $this->getHeader()->getFieldBody('Message-Id');
  }

  /**
   * {@inheritdoc}
   */
  public function getReferences() {
    $references = $this->getHeader()->getFieldBody('References');
    return $references ? explode(' ', $references) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getInReplyTo() {
    $in_reply_to = $this->getHeader()->getFieldBody('In-Reply-To');
    return $in_reply_to ? explode(' ', $in_reply_to) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return $this->getHeader()->getFieldBody('Subject');
  }

  /**
   * {@inheritdoc}
   */
  public function getPlainText() {
    $message_parts = $this->getParts();
    foreach ($message_parts as $key => $part) {
      $content_fields = $part->getContentType();
      $content_type = $content_fields['type'] . '/' . $content_fields['subtype'] ;
      $body = $part->getDecodedBody();

      // The first plaintext or HTML part wins.
      // @todo Consider further parts and concatenate bodies?
      if ($content_type == 'text/plain') {
        return $body;
      }
      else if ($content_type == 'text/html') {
        return strip_tags($body);
      }
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getHtml() {
    foreach ($this->getParts() as $key => $part) {
      $content_type = $part->getContentType()['type'] . '/' . $part->getContentType()['subtype'];
      // The first identified HTML part wins.
      if ($content_type == 'text/html') {
        // @todo: Consider further parts.
        return $part->getDecodedBody();
      }
    }
  }

}
