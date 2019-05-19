<?php

namespace Drupal\stats\Plugin;

use Drupal\stats\Row;

/**
 * Base class for Stat process plugins.
 */
abstract class StatStepBase extends StatPluginBase implements StatStepInterface {

  const SOURCE_PROPERTY = 'source';
  const DESTINATION_PROPERTY = 'destination';

  /**
   * Helper to get source value from a row.
   *
   * @param \Drupal\stats\Row $row
   *
   * @return mixed|null
   */
  protected function getSourceValue(Row $row) {
    $source = $this->configuration[static::SOURCE_PROPERTY];
    return $row->getProperty($source);
  }

  /**
   * Helper to set destination value on a row.
   *
   * @param \Drupal\stats\Row $row
   * @param mixed $value
   */
  protected function setDestinationValue(Row $row, $value) {
    $destination = $this->configuration[static::DESTINATION_PROPERTY];
    $row->setDestinationProperty($destination, $value);
  }


}
