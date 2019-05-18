<?php

/**
 * @file
 * Contains \Drupal\pe_migrate\Plugin\pe_migrate\process\Address.
 */

namespace Drupal\pe_migrate\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * This plugin builds the address field array.
 *
 * @see https://www.drupal.org/node/2143521
 *
 * @MigrateProcessPlugin(
 *   id = "address",
 *   handle_multiples = TRUE
 * )
 */
class Address extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $dependencies = TRUE;
    if (!empty($this->configuration['required'])) {
      $fields = $this->configuration['required'];
      foreach($fields as $field) {
        if(empty($row->getSourceProperty($destination_property . '__' . $field))) {
          $dependencies = FALSE;
          break;
        }
      }
    }

    if ($dependencies) {
      $fields = ['streetAddress', 'addressLocality', 'addressRegion', 'postalCode', 'postOfficeBoxNumber', 'addressCountry'];
      foreach ($fields as $field) {
        if(!empty($row->getSourceProperty($destination_property . '__' . $field))) {
          $value[$field] = $row->getSourceProperty($destination_property . '__' . $field);
        }
      }
    }

    return $value;
  }
}
