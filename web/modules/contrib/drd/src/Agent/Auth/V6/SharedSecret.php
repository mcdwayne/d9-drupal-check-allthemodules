<?php

namespace Drupal\drd\Agent\Auth\V6;

class SharedSecret extends Base {

  /**
   * {@inheritdoc}
   */
  public function validate(array $settings) {
    return ($settings['secret'] === $this->storedSettings['secret']);
  }

}
