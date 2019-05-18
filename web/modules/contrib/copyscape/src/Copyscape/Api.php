<?php

namespace Drupal\copyscape\Copyscape;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;

class Api {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  protected $xmlData;

  protected $xmlDepth;

  protected $xmlRef;

  protected $xmlSpec;

  /**
   * Utility constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \GuzzleHttp\ClientInterface $httpClient
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    ClientInterface $httpClient
  ) {
    $this->configFactory = $configFactory;
    $this->httpClient = $httpClient;
  }

  /**
   * Helper function to work with copyscape API.
   *
   * @param string $text
   * @param string $encoding
   * @param int $full
   *
   * @return array|bool
   */
  public function textSearchInternet($text, $encoding = 'UTF-8', $full = 5) {
    return $this->textSearch($text, $encoding, $full);
  }

  /**
   * Processes the params and calls the copyscape_api_call() to execute search.
   *
   * @param string $text
   *   The text to search for.
   * @param string $encoding
   *   Text encoding, defaults to UTF-8.
   * @param int $full
   *   If not empty, it should be a value between 1 and 10.
   * @param string $operation
   *   Operation to be executed.
   *
   * @return array|bool
   *   The result of the executed search.
   *
   * @see http://www.copyscape.com/api-guide.php#text
   */
  public function textSearch($text, $encoding = 'UTF-8', $full = 0, $operation = 'csearch') {
    $params = ['e' => $encoding];

    if (!empty($full)) {
      $params['c'] = $full;
    }

    return $this->apiCall($operation, $params, [2 => ['result' => 'array']], $text);
  }

  /**
   * Builds the query string and executes the call against Copyscape API.
   *
   * @param string $operation
   *   The operation to be executed.
   * @param array $params
   *   Parameters to be sent.
   * @param array $xmlSpec
   *   XML Spec to use.
   * @param array $postData
   *   The fields to post to the request.
   *
   * @return array|bool
   *   The functions returns FALSE if no response was received
   *     or
   *   the parsed string as an array.
   */
  public function apiCall($operation, $params = [], $xmlSpec = [], $postData = []) {
    $config = $this->configFactory->get('copyscape.settings');

    $copyscapeUrl = $config->get('api_url');
    $copyscapeUser = $config->get('api_user');
    $copyscapeKey = $config->get('api_key');

    // If the settings for accessing Copyscape were not done, exit.
    if (empty($copyscapeUser) || empty($copyscapeKey)) {
      return FALSE;
    }

    // Prepare the query array.
    $query = [
      'u' => $copyscapeUser,
      'k' => $copyscapeKey,
      'o' => $operation,
    ];

    // Merge with $params.
    $query += $params;

    $query = http_build_query($query);
    $url = "${copyscapeUrl}?${query}";

    $response = empty($postData)
      ? $this->httpClient->get($url)
      : $this->httpClient->post($url, ['form_params' => [$postData]]);
    if ($response->getStatusCode() !== 200) {
      return FALSE;
    }

    $content = $response->getBody()->getContents();

    return $this->readXml($content, $xmlSpec);
  }

  /**
   * Parses the xml mark-up given as parameter.
   *
   * @param string $xml
   *   The XML mark-up to parse.
   * @param array $spec
   *   The XML spec to use.
   *
   * @return array|bool
   *   FALSE if $xml is not valid XML mark-up or
   *   Parsed XML as an array if $xml is valid.
   */
  public function readXml($xml, $spec = []) {
    $this->xmlData = [];
    $this->xmlDepth = 0;
    $this->xmlRef = [];
    $this->xmlSpec = $spec;

    $parser = xml_parser_create();

    xml_set_object($parser, $this);
    xml_set_element_handler($parser, 'xmlStart', 'xmlEnd');
    xml_set_character_data_handler($parser, 'xmlData');

    if (!xml_parse($parser, $xml, TRUE)) {
      return FALSE;
    }

    xml_parser_free($parser);

    return $this->xmlData;
  }

  /**
   * XML parser start handler to be used in xml_set_element_handler().
   *
   * @link http://php.net/manual/en/function.xml-set-element-handler.php
   */
  public function xmlStart($parser, $name, $attribs) {
    $this->xmlDepth ++;

    if ($this->xmlDepth === 1) {
      $this->xmlRef[1] = &$this->xmlData;

      return;
    }

    $name = strtolower($name);

    if (!is_array($this->xmlRef[$this->xmlDepth - 1])) {
      $this->xmlRef[$this->xmlDepth - 1] = [];
    }

    if (@$this->xmlSpec[$this->xmlDepth][$name] === 'array') {
      if (!is_array(@$this->xmlRef[$this->xmlDepth - 1][$name])) {
        $this->xmlRef[$this->xmlDepth - 1][$name] = [];
        $key = 0;
      }
      else {
        $key = 1 + max(array_keys($this->xmlRef[$this->xmlDepth - 1][$name]));
      }

      $this->xmlRef[$this->xmlDepth - 1][$name][$key] = '';
      $this->xmlRef[$this->xmlDepth] =
        &$this->xmlRef[$this->xmlDepth - 1][$name][$key];

      return;
    }


    $this->xmlRef[$this->xmlDepth - 1][$name] = '';
    $this->xmlRef[$this->xmlDepth] =
      &$this->xmlRef[$this->xmlDepth - 1][$name];
  }

  /**
   * XML parser end handler to be used in xml_set_element_handler().
   *
   * @link http://php.net/manual/en/function.xml-set-element-handler.php
   */
  public function xmlEnd($parser, $name) {
    unset($this->xmlRef[$this->xmlDepth]);

    $this->xmlDepth --;
  }

  /**
   * XML parser character data handler function.
   *
   * @link http://php.net/manual/en/function.xml-set-character-data-handler.php
   */
  public function xmlData($parser, $data) {
    if (is_string($this->xmlRef[$this->xmlDepth])) {
      $this->xmlRef[$this->xmlDepth] .= $data;
    }
  }

}
