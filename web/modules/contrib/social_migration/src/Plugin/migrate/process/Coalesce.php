<?php

namespace Drupal\social_migration\Plugin\migrate\process;

use Drupal\migrate\Row;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;

/**
 * Take the first non-null/empty property from a list of potential values.
 *
 * Available configuration keys:
 * - source: Source property.
 * - default_value: The value to return if all other values are null/empty.
 *
 * The coalesce plugin returns the first non-null/empty value from a list of
 * properties in the "source" configuration key.
 *
 * Examples:
 *
 * @code
 * process:
 *   baz:
 *     plugin: coalesce
 *     source:
 *       - foo
 *       - bar
 *     default_value: bin
 * @endcode
 *
 * If "foo" was not null/empty, the source value of "foo" will be copied to the
 * destination property "baz"; if "foo" was null/empty and "bar" was not, the
 * source value of "bar" will be copied to "baz"; and if both "foo" and "bar"
 * are null/empty, the hard-coded value of 'bin' will be copied to "baz".
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "coalesce"
 * )
 */
class Coalesce extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $source = $this->configuration['source'];
    $properties = is_string($source) ? [$source] : $source;
    $default = $this->configuration['default_value'] ?: NULL;

    foreach ($properties as $property) {
      if ($return = $row->getSourceProperty($property)) {
        return $return;
      }
    }

    return $default;
  }

}
