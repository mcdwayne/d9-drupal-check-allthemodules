<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Request\Payment;

use Drupal\commerce_klarna_payments\Klarna\Data\Payment\AttachmentInterface;
use Drupal\commerce_klarna_payments\Klarna\Data\Payment\AttachmentItemInterface;
use Drupal\commerce_klarna_payments\Klarna\RequestBase;

/**
 * Value object for attachments.
 */
class Attachment extends RequestBase implements AttachmentInterface {

  protected $data = [];

  protected const CONTENT_TYPE = 'application/vnd.klarna.internal.emd-v2+json';

  /**
   * {@inheritdoc}
   */
  public function setContentType(string $type) : AttachmentInterface {
    $this->data['content_type'] = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBody(AttachmentItemInterface $item) : AttachmentInterface {
    $this->data['body'] = $item;
    return $this;
  }

  /**
   * Gets the body.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\Payment\AttachmentItemInterface|null
   *   The body or NULL.
   */
  public function getBody() : ? AttachmentItemInterface {
    return $this->data['body'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() : array {
    $data = parent::toArray();

    // Populate default content type.
    if (!isset($data['content_type'])) {
      $data['content_type'] = static::CONTENT_TYPE;
    }

    // Body must be json encoded.
    if (isset($data['body'])) {
      $data['body'] = \GuzzleHttp\json_encode($data['body']);
    }

    return $data;
  }

}
