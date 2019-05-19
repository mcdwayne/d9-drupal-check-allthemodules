<?php

namespace Drupal\twitterlogin\Plugin\Network;

/**
 * File for OAuthConsumer Class
 */
class OAuthDataStore {

  /**
   * {@inheritdoc}
   */
  public function lookup_consumer($consumer_key) {
    // Implement me.
  }

  /**
   * {@inheritdoc}
   */
  public function lookup_token($consumer, $token_type, $token) {
    // Implement me.
  }

  /**
   * {@inheritdoc}
   */
  public function lookup_nonce($consumer, $token, $nonce, $timestamp) {
    // Implement me.
  }

  /**
   * {@inheritdoc}
   */
  public function new_request_token($consumer, $callback = NULL) {
    // Return a new token attached to this consumer.
  }

  /**
   * {@inheritdoc}
   */
  public function new_access_token($token, $consumer, $verifier = NULL) {
    // Return a new access token attached to this consumer
    // for the user associated with this token if the request token
    // is authorized.
    // Should also invalidate the request token
  }

}