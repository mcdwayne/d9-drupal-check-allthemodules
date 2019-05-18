<?php

namespace Drupal\migrate_process_extras\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Attempt to fix invalid links.
 *
 * @MigrateProcessPlugin(
 *   id = "link_fix"
 * )
 */
class LinkFix extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    if (!$value) {
      return NULL;
    }

    // Loose check for http in case we have https links.
    if (substr($value, 0, 4) !== 'http') {
      return "http://$value";
    }
    return $value;
  }

}
