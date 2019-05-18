<?php

namespace Drupal\bulk_alias_checker\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * An example controller.
 */

class Sampleform extends ControllerBase{

/**
   * {@inheritdoc}
   */
public function sample() {
  $file = drupal_get_path('module', 'bulk_alias_checker') . '/samplefile/bulk_alias_checker.csv';
  header("Content-type: octet/stream");
  header("Content-disposition: attachment; filename=" . $file . ";");
  header("Content-Length: " . filesize($file));
  readfile($file);
  exit;
}

}

