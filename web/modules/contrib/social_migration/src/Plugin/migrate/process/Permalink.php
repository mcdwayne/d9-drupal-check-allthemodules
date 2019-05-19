<?php

namespace Drupal\social_migration\Plugin\migrate\process;

use Drupal\migrate\Row;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;

/**
 * Create a Twitter permalink.
 *
 * Available configuration keys:
 * - property_name: The property name for this user.
 *
 * @MigrateProcessPlugin(
 *   id = "permalink"
 * )
 */
class Permalink extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $propertyName = $this->configuration['property_name'];
    $id = $row->getSourceProperty('id');
    return "https://twitter.com/${propertyName}/status/${id}";
  }

}
