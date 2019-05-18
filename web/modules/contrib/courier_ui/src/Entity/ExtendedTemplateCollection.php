<?php

namespace Drupal\courier_ui\Entity;

use Drupal\courier\Entity\TemplateCollection;

/**
 * Extends Courier Template Collection class.
 */
class ExtendedTemplateCollection extends TemplateCollection {

  /**
   * {@inheritdoc}
   */
  public function label() {
    $label = NULL;
    if (isset($this->title)) {
      $title_field = $this->title->getValue();
      if (!empty($title_field[0]['value'])) {
        $label = $title_field[0]['value'];
      }
    }
    return $label;
  }

}
