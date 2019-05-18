<?php

namespace Drupal\drd\Agent\Auth\V8;

/**
 * Implements the SharedSecret authentication method.
 */
class SharedSecret extends Base {

  /**
   * {@inheritdoc}
   */
  public function validate(array $settings) {
    return ($settings['secret'] === $this->storedSettings['secret']);
  }

}
