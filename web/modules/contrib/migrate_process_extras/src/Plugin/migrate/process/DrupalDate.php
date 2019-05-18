<?php

namespace Drupal\migrate_process_extras\Plugin\migrate\process;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Custom process plugin to make handling Drupal dates easy.
 *
 * @MigrateProcessPlugin(
 *   id = "drupal_date"
 * )
 */
class DrupalDate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!$value) {
      return $value;
    }

    $format = empty($this->configuration['format']) ? 'j/m/Y' : $this->configuration['format'];
    $timezone = empty($this->configuration['timezone']) ? 'australia/sydney' : $this->configuration['timezone'];
    $dateTime = DrupalDateTime::createFromFormat($format, $value, new \DateTimeZone($timezone));
    $storage_format = empty($this->configuration['storage_format']) ? 'datetime' : $this->configuration['storage_format'];
    return $dateTime->format($this->getStorageFormat($storage_format));
  }

  /**
   * Gets the Drupal specific storage formats.
   *
   * @param string $storage_format
   *   Either datetime or date.
   *
   * @return string
   *   The Drupal date storage format.
   */
  protected function getStorageFormat($storage_format) {
    if ($storage_format === 'datetime') {
      return DATETIME_DATETIME_STORAGE_FORMAT;
    }
    if ($storage_format === 'date') {
      return DATETIME_DATE_STORAGE_FORMAT;
    }

    throw new \InvalidArgumentException('Invalid storage format ' . $storage_format);
  }

}
