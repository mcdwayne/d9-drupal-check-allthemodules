<?php

namespace Drupal\twitterlogin\Plugin\Network;

/**
 * File for OAuthConsumer Class.
 */
class OAuthConsumer {
  public $key;
  public $secret;

  /**
   * {@inheritdoc}
   */
  public function __construct($key, $secret, $callback_url = NULL) {
    $this->key = $key;
    $this->secret = $secret;
    $this->callback_url = $callback_url;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return "OAuthConsumer[key=$this->key,secret=$this->secret]";
  }

}
