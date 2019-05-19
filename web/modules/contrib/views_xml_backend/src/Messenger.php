<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Messenger.
 */

namespace Drupal\views_xml_backend;

/**
 * The default messenger.
 */
class Messenger implements MessengerInterface {

  /**
   * {@inheritdoc}
   */
  public function setMessage($message = NULL, $type = 'status', $repeat = FALSE) {
    drupal_set_message($message, $type, $repeat);
  }

}
