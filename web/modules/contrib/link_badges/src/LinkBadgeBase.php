<?php

namespace Drupal\link_badges;


class LinkBadgeBase implements LinkBadgeInterface {

  /**
   * {@inheritdoc}
   */
  public function getBadgeValue() {
    return NULL;
  }

  /**
   * @param string $property
   * @param mixed $value
   */
  public function set($property, $value) {
    $this->{$property} = $value;
  }

}
