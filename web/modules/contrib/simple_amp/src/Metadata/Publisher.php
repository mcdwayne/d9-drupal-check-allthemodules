<?php

namespace Drupal\simple_amp\Metadata;

use Drupal\simple_amp\Metadata\Base;

/**
 * Generate AMP Publisher metadata.
 */
class Publisher extends Base {

  protected $type = 'Organization';
  protected $name;
  protected $logo;

  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  public function getName() {
    return $this->name;
  }

  public function setLogo(Image $logo) {
    $this->logo = $logo;
    return $this;
  }

  public function getlogo() {
    return is_a($this->logo, '\Drupal\simple_amp\Metadata\Image') ? $this->logo->build() : '';
  }

  public function build() {
    $params = [];
    if ($type = $this->getType()) {
      $params['@type'] = $type;
    }
    if ($name = $this->getName()) {
      $params['name'] = $name;
    }
    if ($logo = $this->getlogo()) {
      $params['logo'] = $logo;
    }
    return $params;
  }

}
