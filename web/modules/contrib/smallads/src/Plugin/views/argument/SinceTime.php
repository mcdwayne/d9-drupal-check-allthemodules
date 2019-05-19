<?php

namespace Drupal\smallads\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Filter results getting everything BEFORE the passed unixtime.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("smallad_since_time")
 */
class SinceTime extends ArgumentPluginBase {

  /**
   * Build the query based upon the formula.
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $this->query->addWhere(0, "$this->tableAlias.$this->realField", $this->argument, '>');
  }

}
