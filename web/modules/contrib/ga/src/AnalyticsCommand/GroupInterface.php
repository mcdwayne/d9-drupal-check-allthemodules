<?php

namespace Drupal\ga\AnalyticsCommand;

/**
 * Interface GroupInterface.
 */
interface GroupInterface {

  /**
   * A key to identify this group.
   *
   * @return string
   *   Group name.
   */
  public function getGroupKey();

}
