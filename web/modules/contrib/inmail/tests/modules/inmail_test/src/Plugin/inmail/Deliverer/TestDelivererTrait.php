<?php

namespace Drupal\inmail_test\Plugin\inmail\Deliverer;

/**
 * Test trait for Deliverer and Fetchers plugins.
 */
trait TestDelivererTrait {

  abstract function makeStateKey($key);

  /**
   * Returns success state.
   *
   * @return string
   *   The succeeded message key.
   */
  public function getSuccess() {
    return \Drupal::state()->get($this->makeStateKey('success'));
  }

  /**
   * Sets success state.
   *
   * @param string $key
   *   The succeeded message key.
   */
  public function setSuccess($key) {
    \Drupal::state()->set($this->makeStateKey('success'), $key);
  }

}
