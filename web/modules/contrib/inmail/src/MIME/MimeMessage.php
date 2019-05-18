<?php

namespace Drupal\inmail\MIME;

/**
 * Models an email message.
 *
 * @ingroup mime
 */
class MimeMessage extends MimeEntity implements MimeMessageInterface {

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
    $content_fields = $this->getContentType();
    $content_type = $content_fields['type'] . '/' . $content_fields['subtype'] ;
    if ($content_type == 'text/plain') {
      return $this->getDecodedBody();
    }
    else if ($content_type == 'text/html') {
      return strip_tags($this->getDecodedBody());
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getHtml() {
    $content_type = $this->getContentType()['type'] . '/' . $this->getContentType()['subtype'];
    return $content_type == 'text/html' ? $this->getDecodedBody() : '';
  }

}
