<?php

namespace Drupal\appnexus;

/**
 * Generate parameters for apntag.defineTag() method.
 */
class Tag {

  protected $tagId;
  protected $sizes = [];
  protected $targetId;
  protected $position;

  public function setTagId($id) {
    $this->tagId = $id;
    return $this;
  }

  public function getTagId() {
    return (int) $this->tagId;
  }

  public function setSizes($sizes) {
    $this->sizes = $sizes;
    return $this;
  }

  public function getSizes() {
    $result = [];
    foreach ($this->sizes as $item) {
      $sizes = [];
      $pair = explode(',', strtolower($item['sizes']));
      if (count($pair)) {
        foreach ($pair as $size) {
          $sizes[] = explode('x', $size);
        }
      }
      else {
        $sizes = explode('x', $pair);
      }
      if (!empty($item['minWidth']) && !empty($sizes)) {
        $result[] = [
          'minWidth' => $item['minWidth'],
          'sizes'    => $sizes,
        ];
      }
    }
    return $result;
  }

  public function setTargetId($id) {
    $this->targetId = $id;
    return $this;
  }

  public function getTargetId() {
    return $this->targetId;
  }

  public function setPosition($position) {
    $this->position = $position;
    return $this;
  }

  public function getPosition() {
    return $this->position;
  }

  public function build() {
    $opts = [];
    if ($tagId = $this->getTagId()) {
      $opts['tagId'] = $tagId;
    }
    if ($sizeMapping = $this->getSizes()) {
      $opts['sizeMapping'] = $sizeMapping;
    }
    if ($position = $this->getPosition()) {
      $opts['position'] = $position;
    }
    if ($targetId = $this->getTargetId()) {
      $opts['targetId'] = $targetId;
    }
    return $opts;
  }

}
