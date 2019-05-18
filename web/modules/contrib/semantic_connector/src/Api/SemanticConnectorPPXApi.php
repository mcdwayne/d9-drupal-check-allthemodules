<?php

namespace Drupal\semantic_connector\Api;
use Drupal\semantic_connector\SemanticConnectorCurlConnection;

/**
 * Abstract Class SemanticConnectorPPXApi
 *
 * API Class for the PoolParty Extractor.
 */
abstract class SemanticConnectorPPXApi {

  protected $connection;
  protected $apiVersion;

  /**
   * The constructor of the PoolParty Extractor class.
   *
   * @param string $endpoint
   *   URL of the endpoint of the PoolParty-server.
   * @param string $credentials
   *   Username and password if required (format: "username:password").
   */
  public function __construct($endpoint, $credentials = '') {
    $this->connection = new SemanticConnectorCurlConnection($endpoint, $credentials);
    $this->apiVersion = str_replace(array('Drupal\semantic_connector\Api\SemanticConnectorPPXApi_', '_'), array('', '.'), get_class($this));
  }

  /**
   * Get the configured cURL-connection.
   *
   * @return SemanticConnectorCurlConnection
   *   The connection object.
   */
  public function getConnection() {
    return $this->connection;
  }

  /**
   * Get the configured used API version.
   *
   * @return string
   *   The API version.
   */
  public function getApiVersion() {
    return $this->apiVersion;
  }

  /**
   * Get the path to the PPX API.
   *
   * @return string
   *   The path to the PPX API.
   */
  public function getApiPath() {
    // Use API versioning for version 6.1+.
    return '/extractor/' . (version_compare($this->apiVersion, '6.1', '>=') ? $this->apiVersion . '/' : '') . 'api/';
  }

  /**
   * This method checks if the PoolParty server exists and is running.
   *
   * @return array
   *   Associative array which following properties:
   *   - success (boolean): TRUE if a connection to the server can be
   *     established.
   *   - message (string): This property is optional, but if it exists it
   *     includes more details about why the connection could not be
   *     established.
   */
  public function available() {
    return array('success' => FALSE);
  }

  /**
   * Get a list of available projects of a PoolParty server.
   *
   * @return array
   *   An array of projects found on the PoolParty available for the current
   *   PoolParty user.
   */
  public function getProjects() {
    return array();
  }

  /**
   * Extract concepts from given data.
   *
   * @param mixed $data
   *   Can be either a string for normal text-extraction of a file-object for
   *   text extraction of the file content.
   * @param string $language
   *   The iso-code of the text's language.
   * @param array $parameters
   *   Additional parameters to forward to the API (e.g. projectId).
   * @param string $data_type
   *   The type of the data. Can be one of the following values:
   *   - "text" for a text
   *   - "url" for a valid URL
   *   - "file" for a file object with a file ID
   *   - "file direct" for all other files without an ID
   * @param boolean $categorize
   *   TRUE if categories should also be returned, FALSE if not.
   *
   * @return array
   *   Array of concepts.
   */
  public function extractConcepts($data, $language, array $parameters = array(), $data_type = '', $categorize = FALSE) {
    return NULL;
  }

  /**
   * Extract categories from given data.
   *
   * @param mixed $data
   *   Can be either a string for normal text-extraction of a file-object for
   *   text extraction of the file content.
   * @param string $language
   *   The iso-code of the language of the data.
   * @param array $parameters
   *   Additional parameters to forward to the API (e.g., projectId).
   * @param string $data_type
   *   The type of the data. Can be one of the following values:
   *   - "text" for a text
   *   - "url" for a valid URL
   *   - "file" for a file object with a file ID
   *   - "file direct" for all other files without an ID
   *
   * @return object
   *   Object of categories.
   */
  public function extractCategories($data, $language, array $parameters = array(), $data_type = '') {
    return NULL;
  }

  /**
   * Get a list of of concepts / free terms matching a string.
   *
   * @param string $string
   *   The string to search matching concepts / freeterms for.
   * @param string $language
   *   The iso-code of the text's language.
   * @param string $project_id
   *   The ID of the PoolParty project to use.
   * @param array $parameters
   *   Additional parameters to forward to the API (e.g. projectId).
   *
   * @return array
   *   An associative array of concepts and freeterms.
   */
  public function suggest($string, $language, $project_id, array $parameters = array()) {
    return array();
  }

  /**
   * Get the type of $data.
   *
   * @param mixed $data
   *   The data.
   *
   * @return string
   *   The type of the data. Can be one of the following values:
   *   - "text" for a text
   *   - "url" for a valid URL
   *   - "file" for a file object with a file ID
   *   - "file direct" for all other files without an ID
   *   - empty if no type was identified
   */
  protected function getTypeOfData($data) {
    $data_type = '';

    if (is_string($data) && valid_url($data)) {
      $data_type = 'url';
    }
    elseif (is_string($data)) {
      $data_type = 'text';
    }
    elseif (is_object($data) && property_exists($data, 'fid')) {
      $data_type = 'file';
    }
    elseif (is_object($data) && property_exists($data, 'file_path')) {
      $data_type = 'file direct';
    }

    return $data_type;
  }

  /**
   * Extract categories from given data.
   *
   * @param string $text
   *   The text to extract named entities for.
   * @param string $language
   *   The iso-code of the language of the data.
   * @param array $types
   *   The types of entities to extract.
   * @param array $parameters
   *   Additional parameters to forward to the API.
   *
   * @return array
   *   An array of named entity objects.
   */
  public function extractNamedEntities($text, $language, array $types, $parameters = []) {
    return [];
  }
}