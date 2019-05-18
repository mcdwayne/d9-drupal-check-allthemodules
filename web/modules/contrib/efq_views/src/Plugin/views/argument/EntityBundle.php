<?php

/**
 * @file
 * Definition of Drupal\efq_views\Plugin\views\argument\EntityBundle.
 */

namespace Drupal\efq_views\Plugin\views\argument;

/**
 * Basic argument handler to implement entity bundle arguments.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("efq_entity_bundle")
 */

/**
 * Argument handler for entity bundles.
 */
class EntityBundle extends EntityString {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->query->query->entityCondition($this->real_field, $this->argument, '=');
  }

}
