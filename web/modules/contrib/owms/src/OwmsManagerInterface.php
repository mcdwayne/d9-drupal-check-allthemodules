<?php

namespace Drupal\owms;

use Drupal\owms\Entity\OwmsData;
use Drupal\owms\Entity\OwmsDataInterface;
use GuzzleHttp\Psr7\Uri;

/**
 * Interface OwmsManagerInterface.
 */
interface OwmsManagerInterface {

  /**
   * Checks if the entered endpoint ID is correct and returns a valid XML.
   *
   * @param string $endpoint
   *
   * @return \SimpleXMLElement
   *   Returns the XML
   */
  public function validateEndpoint($endpoint);

  /**
   * Returns a valid XML object with the endpoint configured in the OwmsData object.
   *
   * @param string $endpoint
   *   The Endpoint URL.
   *
   * @return \SimpleXMLElement Returns the XML

   * @throws \Exception
   *   When Xml or HTTP response are not valid.
   */
  public function fetchXmlFromEndpoint($endpoint);

  /**
   * Gets an array of possible endpoints.
   *
   * @return array
   */
  public function getEndpoints();

  /**
   * Parses the XML provided into a Configuration object.
   *
   * @param \SimpleXMLElement $xml
   *
   * @return array
   *   An array of items containing the following keys:
   *   - identifier
   *   - label
   *   - deprecated
   */
  public function parseDataValues(\SimpleXMLElement $xml);

  /**
   * Check for updates and process them.
   *
   * @param \Drupal\owms\Entity\OwmsDataInterface $owmsData
   *
   * @return bool
   *   Returns TRUE if successful.
   *
   * @throws \Exception
   *   If anything goes wrong.
   */
  public function updateItems(OwmsDataInterface $owmsData);

  /**
   * Collects fields and entities with deprecated OWMS values.
   *
   * @return array
   *   An array keyed by entity types, bundles, field names and entity ids.
   */
  public static function collectDeprecatedFieldValues();

}
