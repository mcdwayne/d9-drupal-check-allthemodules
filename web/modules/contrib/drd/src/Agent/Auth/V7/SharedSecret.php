<?php

namespace Drupal\drd\Agent\Auth\V7;

class SharedSecret extends Base {

  /**
   * {@inheritdoc}
   */
  public function validate(array $settings) {
    return ($settings['secret'] === $this->storedSettings['secret']);
  }

}
