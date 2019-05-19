<?php

namespace Drupal\webform_digests;

use Drupal\webform_digests\Entity\WebformDigestInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Handler for sending out digest messages.
 */
interface WebformDigestsMailHandlerInterface {

  /**
   * Take a webform digest and compile and send the message.
   */
  public function sendMessage(WebformDigestInterface $webformDigest, EntityInterface $entity);

}
