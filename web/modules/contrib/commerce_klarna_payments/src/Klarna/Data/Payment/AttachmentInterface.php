<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data\Payment;

use Drupal\commerce_klarna_payments\Klarna\Data\ObjectInterface;

/**
 * An interface to describe attachments.
 */
interface AttachmentInterface extends ObjectInterface {

  /**
   * Sets the content type.
   *
   * @param string $type
   *   The type.
   *
   * @return $this
   *   The self.
   */
  public function setContentType(string $type) : AttachmentInterface;

  /**
   * Sets the attachment body.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\Payment\AttachmentItemInterface $item
   *   The item.
   *
   * @return $this
   *   The self.
   */
  public function setBody(AttachmentItemInterface $item) : AttachmentInterface;

}
