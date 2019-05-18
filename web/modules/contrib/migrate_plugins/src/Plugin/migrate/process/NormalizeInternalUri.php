<?php

namespace Drupal\migrate_plugins\Plugin\migrate\process;

use Drupal\Component\Utility\UrlHelper;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a 'NormalizeInternalUri' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "normalize_internal_uri"
 * )
 */
class NormalizeInternalUri extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $path = trim($value);

    // Ignore absolute URLS.
    if (UrlHelper::isValid($path, TRUE)) {
      return $path;
    }

    if (empty($path)) {
      return $path;
    }

    // Remove leading slash.
    $path = preg_replace('#^/+#', '', $path);

    // Prepend the internal schema when missing.
    if (strpos($path, 'internal:/') === FALSE) {
      $path = 'internal:/' . $path;
    }

    return $path;
  }

}
