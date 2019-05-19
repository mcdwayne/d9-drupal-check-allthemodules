<?php

namespace Drupal\simple_amp\Metadata;

use Drupal\simple_amp\Metadata\Base;

/**
 * Generate AMP Author metadata.
 */
class Author extends Base {

  protected $type = 'Person';
  protected $name;

  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  public function getName() {
    return $this->name;
  }

  public function build() {
    $params = [];
    if ($type = $this->getType()) {
      $params['@type'] = $type;
    }
    if ($name = $this->getName()) {
      $params['name'] = $name;
    }
    return $params;
  }

}
