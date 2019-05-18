<?php

/**
 * @file
 * Contain Top(n) record type.
 */

namespace Drupal\g2;

/**
 * Class TopRecord contains the top(n) individual record type.
 */
class TopRecord {
  /**
   * The author login name.
   *
   * @var string
   */
  public $name;

  /**
   * The node id. May be a numeric string because of MySQL weak typing.
   *
   * @var int
   */
  public $nid;

  /**
   * The node title. A plain text string.
   *
   * @var string
   */
  public $title;

  /**
   * The author user id. May be a numeric string because of MySQL weak typing.
   *
   * @var int
   */
  public $uid;

  /**
   * The views count for the requested statistic. May be a numeric string.
   *
   * @var int
   */
  public $views;

  /**
   * Fix field types: PDO MySQL returns everything as strings.
   */
  public function normalize() {
    foreach (['nid', 'uid', 'views'] as $property) {
      $this->{$property} = intval($this->{$property});
    }
  }

}
