<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\argument\Passthrough.
 */

namespace Drupal\views_xml_backend\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Passthrough XML argument handler.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("views_xml_backend_passthrough")
 */
class Passthrough extends ArgumentPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    // Prevent this query from being added.
  }

}
