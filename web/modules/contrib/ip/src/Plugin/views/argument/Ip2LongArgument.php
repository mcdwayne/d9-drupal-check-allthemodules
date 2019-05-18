<?php

/**
 * @file
 * Contains \Drupal\ip\Plugin\views\argument\Ip2LongArgument.
 */

namespace Drupal\ip\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Ip2long implementation of the base argument plugin.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("ip2long")
 */
class Ip2LongArgument extends ArgumentPluginBase {

  function query($group_by = FALSE) {
    $this->ensureMyTable();
    $this->query->addWhere(0, "$this->tableAlias.$this->realField", ip2long($this->argument));
  }
}
