<?php

namespace Drupal\views_timelinejs\TimelineJS;

/**
 * Provides an interface for defining TimelineJS3 objects.
 */
interface ObjectInterface {

  /**
   * Creates an array representing the TimelineJS javascript object.
   *
   * @return mixed[]
   *   The formatted array.
   */
  public function buildArray();

}
