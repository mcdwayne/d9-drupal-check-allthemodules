<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\MessengerInterface.
 */

namespace Drupal\views_xml_backend;

/**
 * Allows a plugable drupal_set_message() implementation.
 */
interface MessengerInterface {

  /**
   * Sets a message to display to the user.
   *
   * @see drupal_set_message()
   */
  public function setMessage($message = NULL, $type = 'status', $repeat = FALSE);

}
