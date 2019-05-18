<?php

namespace Drupal\past;

use Drupal\past\PastEventArgumentInterface;

/**
 * Null implementation that is used as a fallback or when logging is disabled.
 */
class PastEventArgumentNull implements PastEventArgumentInterface {

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRaw() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setRaw($data, $json_encode = TRUE) {

  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalData() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function ensureType() {

  }
}
