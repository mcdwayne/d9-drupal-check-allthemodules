<?php

namespace Drupal\akismet\Client;

/**
 * Drupal Akismet client implementation using Akismet testing mode.
 */
class DrupalTestClient extends DrupalClient {

  /**
   * {@inheritdoc}
   */
  public function query($method, $path, $data, $authenticate = TRUE) {
    $data['is_test'] = 1;
    return parent::query($method, $path, $data, $authenticate);
  }
}
