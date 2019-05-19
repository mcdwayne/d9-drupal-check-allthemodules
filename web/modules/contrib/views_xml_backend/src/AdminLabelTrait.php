<?php

/**
 * @file
 * Contains \Drupal\views_xml_backendAdminLabelTrait.
 */

namespace Drupal\views_xml_backend;

/**
 * Helps views plugins display a useful admin label.
 */
trait AdminLabelTrait {

  /**
   * Returns a string representing this handler's name in the UI.
   *
   * @param bool $short
   *   Whether to return a short label.
   *
   * @return string
   *   The admin label.
   *
   * @see \Drupal\views\Plugin\views\ViewsHandlerInterface::adminLabel()
   */
  public function adminLabel($short = FALSE) {
    if (!empty($this->options['admin_label'])) {
      return $this->options['admin_label'];
    }

    $title = ($short && isset($this->definition['title short'])) ? $this->definition['title short'] : $this->definition['title'];

    return $this->t('@xpath: @title', ['@xpath' => $this->options['xpath_selector'], '@title' => $title]);
  }

}
