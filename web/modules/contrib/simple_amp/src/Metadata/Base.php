<?php

namespace Drupal\simple_amp\Metadata;

/**
 * Generate AMP base metadata.
 */
class Base {

  protected $context;
  protected $type;

  public function setContext($value) {
    $this->context = $value;
    return $this;
  }

  public function getContext() {
    return !empty($this->context) ? $this->context : 'http://schema.org';
  }

  public function setType($value) {
    $this->type = $value;
    return $this;
  }

  public function getType() {
    return $this->type;
  }

}
