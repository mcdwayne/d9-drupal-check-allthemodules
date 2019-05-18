<?php

namespace Drupal\commerce_migrate_csv_example\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Sets attributes on the destination.
 *
 * The attribute name is constructed during the migration, it is prepended with
 * 'attribute_' and the length checked. Since the name is not known beforehand
 * the destination for the attribute values on the product variation is set by
 * this process plugin.
 *
 * The input is a array of values, in pairs. The first element is the array
 * output from 'import_attribute', which supplies the verified name. The second
 * element is the value of the attribute.
 *
 * Example:
 *
 * @code
 * process:
 *   anyname:
 *     plugin: csv_example_attribute_destination
 *     source:
 *       - attribute1_name
 *       - attribute1_value
 *       - attribute2_name
 *       - attribute2_value
 * @endcode
 *
 * If attribute1_name is 'color', attribute_1_value is 'green', attribute2_name
 * is 'size' and attribute2_name is 'Med' then destination property 'color' is
 * set to 'red' and destination property 'size' is set to 'Med'.
 *
 * @throws \Drupal\migrate\MigrateException
 *   Thrown when there is not an even number of elements in the input array.
 *
 * @MigrateProcessPlugin(
 *   id = "csv_example_attribute_destination"
 * )
 */
class AttributeDestination extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value)) {
      $count = count($value);
      if (!($count & 1)) {
        for ($i = 0; $i < $count; $i = $i + 2) {
          if (!empty($value[$i]) && !empty($value[$i + 1])) {
            $field_name = 'attribute_' . $value[$i];
            $field_name = substr($field_name, 0, 32);
            $row->setDestinationProperty($field_name, $value[$i + 1]);
          }
        }
      }
      else {
        throw new MigrateException('There must be an even number of input values.');
      }
    }
  }

}
