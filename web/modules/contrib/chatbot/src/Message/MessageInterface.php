<?php

namespace Drupal\chatbot\Message;

/**
 * Base interface for message classes.
 */
interface MessageInterface {

  /**
   * Retrieve formatted message contents.
   *
   * @return array
   *   A structured message to be sent back to the service provider.
   */
  public function getFormattedMessage();

}
