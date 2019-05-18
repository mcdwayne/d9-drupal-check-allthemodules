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
 *   id = "xpath"
 * )
 *
 */
class Xpath extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Skip everything if the value is empty.
    if (empty($value)) {
      return NULL;
    }

    // Parse the xml in the value.
    try {
      $xml = new \SimpleXMLElement($value, \LIBXML_NOWARNING);
    }
    catch (\Exception $e) {
      // If the XML cannot be parsed, return NULL.
      return NULL;
    }

    // This plugin needs an xpath parameter to function.
    if (empty($this->configuration['xpath'])) {
      throw new MigrateException('Missing required parameter: xpath');
    }

    // Get the xpath from config.
    $xpath = $this->configuration['xpath'];

    // Run the xpath.
    $results = $xml->xpath($xpath);

    $method = isset($this->configuration['method']) ? $this->configuration['method'] : 'xml';

    // Reserialize the output as XML.
    $out = [];
    foreach ($results as $result) {
      if ($method == 'string') {
        $out[] = $result->__toString();
      }
      elseif ($method == 'array') {
        $out[] = (array) $result;
      }
      else {
        $out[] = $result->asXML();
      }
    }

    // Return NULL if no results.
    if (empty($out)) {
      return NULL;
    }

    return $out;
  }
}
