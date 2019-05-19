<?php

namespace Drupal\simple_amp\Metadata;

use Drupal\simple_amp\Metadata\Base;

/**
 * Generate AMP Image metadata.
 */
class Image extends Base {

  protected $type = 'ImageObject';
  protected $url;
  protected $width;
  protected $height;

  public function setUrl($url) {
    $this->url = $url;
    return $this;
  }

  public function getUrl() {
    return $this->url;
  }

  public function setWidth($width) {
    $this->width = $width;
    return $this;
  }

  public function getWidth() {
    return $this->width;
  }

  public function setHeight($height) {
    $this->height = $height;
    return $this;
  }

  public function getHeight() {
    return $this->height;
  }

  public function build() {
    $params = [];
    if ($type = $this->getType()) {
      $params['@type'] = $type;
    }
    if ($url = $this->getUrl()) {
      $params['url'] = $url;
    }
    if ($width = $this->getWidth()) {
      $params['width'] = $width;
    }
    if ($height = $this->getHeight()) {
      $params['height'] = $height;
    }
    return $params;
  }

}
