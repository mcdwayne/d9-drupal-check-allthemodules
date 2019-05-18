<?php

namespace Drupal\phpmail_alter\Controller;

/**
 * Mail backend, using PHP's native mail() function.
 */
class PhpMail {

  /**
   * Deprecated! Backward compatibility.
   */
  public static function mail(array $message) {
    \Drupal::logger('phpmail_alter')->warning("Deprecated: " . __CLASS__);
    return \Drupal::service('phpmail_alter')->mail($message);
  }

}
