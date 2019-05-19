<?php

namespace Drupal\twitterlogin\Plugin\Network;

/**
 * File for OAuthException Class.
 */
if (!class_exists('OAuthException')) {
  /**
   * {@inheritdoc}
   */
  class OAuthException extends Exception {
    // Pass.
  }
}
