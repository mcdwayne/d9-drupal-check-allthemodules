<?php

namespace Drupal\pcr;

trait UpdateElementPropertyTrait {

  // Update element properties.
  public function updateElementProperty($element) {
    $element['#theme'] = 'elements__pretty_options';
    $element['#title_display'] = 'invisible';
    return $element;
  }

}
