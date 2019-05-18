<?php


namespace Drupal\migrate_process_xml\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Dumps the input value to stdout. Passes the rest through.
 *
 * @MigrateProcessPlugin(
 *   id = "xvalue"
 * )
 *
 */
class Xvalue extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Skip everything if the value is empty.
    if (empty($value)) {
      return NULL;
    }

    // Create the DOMDocument object.
    $xml = new \DOMDocument();

    // Parse the xml in the value.
    $xml->loadXML($value, \LIBXML_NOWARNING);

    // This plugin needs an xpath parameter to function.
    if (empty($this->configuration['xpath'])) {
      throw new MigrateException('Missing required parameter: xpath');
    }

    // Create the XPath object.
    $xpath = new \DOMXPath($xml);

    // Get the xpath from config,
    $query = 'string(' . $this->configuration['xpath'] . ')';

    // Run the xpath.
    $results = $xpath->evaluate($query);

    return $results;
  }
}
