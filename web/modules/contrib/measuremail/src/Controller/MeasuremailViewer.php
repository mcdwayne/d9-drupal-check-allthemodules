<?php

namespace Drupal\measuremail\Controller;

use Drupal\measuremail\Entity\Measuremail;

class MeasuremailViewer {

  /**
   * Returns a page title.
   */
  public function getTitle(Measuremail $measuremail) {
    return $measuremail->label();
  }

}
