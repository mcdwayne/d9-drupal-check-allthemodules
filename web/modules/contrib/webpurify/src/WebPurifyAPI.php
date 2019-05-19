<?php

namespace Drupal\webpurify;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;

class WebPurifyAPI {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Stores the API key.
   *
   * @var int
   */
  protected $apiKey;

  /**
   * @var string
   */
  protected $apiMethod = 'POST';

  /**
   * The base url of the WebPurify API.
   */
  const APIEndpoint = 'http://api1.webpurify.com/services/rest/';

  /**
   * Construct a WebPurifyAPI object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   *
   * @todo Throw the exception when the api key is not set.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->apiKey = $this->configFactory->get('webpurify.settings')
      ->get('webpurify_secret_key');
  }

  /**
   * Function to make request through httpClient service.
   *
   * @param $data .
   *  The object to be passed during the API call.
   *
   * @return An array obtained in response from the API call.
   */
  public function postRequest($data) {
    $url = static::APIEndpoint;
    $params = http_build_query($data);
    $response = $this->httpClient->post($url, [
      RequestOptions::BODY => $params,
      RequestOptions::HEADERS => ['Content-Type' => 'application/x-www-form-urlencoded'],
    ]);
    $xml = $response->getBody()->getContents();

    return $this->webpurify_parse_xml($xml);
  }

  /**
   * Return count of profane words.
   */
  public function count($text) {
    if (!empty($text)) {
      $config = \Drupal::config('webpurify.settings');
      $data = [
        'api_key' => $config->get('webpurify_secret_key'),
        'method' => 'webpurify.live.count',
        'text' => $text,
      ];
      $result = $this->postRequest($data);
    }

    return (int) !empty($result['RSP']['#children']['FOUND']['#value']) ? $result['RSP']['#children']['FOUND']['#value'] : 0;
  }

  /**
   * Returns the text with all profane words replaced.
   *
   * @param $text
   * @return array|string
   */
  public function replace($text, $replacement_symbol = '') {
    if (!empty($text)) {
      $config = \Drupal::config('webpurify.settings');
      $data = [
        'api_key' => $config->get('webpurify_secret_key'),
        'method' => 'webpurify.live.replace',
        'text' => $text,
        'replacesymbol' => !empty($replacement_symbol)
            ? $replacement_symbol
            : $config->get('webpurify_replacement_symbol'),
      ];
      $result = $this->postRequest($data);
    }

    return !empty($result['RSP']['#children']['TEXT']['#value']) ? $result['RSP']['#children']['TEXT']['#value'] : $text;
  }

  /**
   * Gets XML in a string and parses it into an array.
   *
   * @param $xml
   *   The source XML to parse.
   *
   * @return array|bool
   *  Parsed XML, or FALSE if there are problems with the XML structure.
   */
  public function webpurify_parse_xml($xml) {
    $parser = NULL;
    $structure = array();
    $index = array();

    // The WebPurify API doesn't wrap the text in cdata, so we will try to fix it.
    if (!preg_match('@<text>\s*<!\[CDATA\[@im', $xml)) {
      $xml = preg_replace('@<text>@im', '<text><![CDATA[', $xml);
      $xml = preg_replace('@</text>@im', ']]></text>', $xml);
    }

    // Did we get any xml?
    if ($xml == "") {
      $GLOBALS['WEBPURIFY_ERROR'] = t("xml was empty");
      return FALSE;
    }

    // Create the parser object.
    if (!($parser = xml_parser_create())) {
      $GLOBALS['WEBPURIFY_ERROR'] = t("xml_parser_create() failed to return parser");
      return FALSE;
    }

    // Try to parse the xml.
    if (xml_parse_into_struct($parser, trim($xml), $structure, $index) === 0) {
      $err_code = xml_get_error_code($parser);
      $err_string = xml_error_string($err_code);
      $GLOBALS['WEBPURIFY_ERROR'] = t("xml_parse_into_struct failed: Code @code - @msg", array(
        '@code' => $err_code,
        '@msg' => $err_string
      ));
      xml_parser_free($parser);
      return FALSE;
    }
    xml_parser_free($parser);

    // Return the parsed xml.
    return $this->webpurify_parse_xml_helper($structure);
  }

  /**
   * Private helper for recusively parsing the result.
   *
   * @param $input
   *   Raw structure of the XML.
   * @param int $depth
   *   Depth of parsed XML.
   *
   * @return array
   *  Parsed XML.
   */
  function webpurify_parse_xml_helper($input, $depth = 1) {
    $output = array();
    $children = array();
    $attributes = FALSE;

    foreach ($input as $data) {
      if (!isset($data['attributes'])) {
        $data['attributes'] = NULL;
      }
      if ($data['level'] == $depth) {
        switch ($data['type']) {
          case 'complete':
            $value = isset($data['value']) ? $data['value'] : NULL;
            $element = array(
              '#tag' => $data['tag'],
              '#value' => $value,
            );
            if ($data['attributes']) {
              $element['#attributes'] = $data['attributes'];
            }

            // See if we need to convert from single element to an array of elements.
            if (isset($output[$data['tag']]['#tag'])) {
              $temp_element = $output[$data['tag']];
              $output[$data['tag']] = array();
              $output[$data['tag']][] = $temp_element;
              $output[$data['tag']][] = $element;
            }
            // Already an array of elements.
            elseif (isset($output[$data['tag']]) && is_array($output[$data['tag']])) {
              $output[$data['tag']][] = $element;
            }
            // A single element.
            else {
              $output[$data['tag']] = $element;
            }
            break;

          case 'open':
            $children = array();
            $attributes = FALSE;
            if ($data['attributes']) {
              $attributes = $data['attributes'];
            }
            break;

          case 'close':
            $element = array(
              '#tag' => $data['tag'],
              '#children' => $this->webpurify_parse_xml_helper($children, $depth + 1),
            );
            if ($attributes) {
              $element['#attributes'] = $attributes;
            }

            // See if we need to convert from single element to an array of elements.
            if (isset($output[$data['tag']]['#tag'])) {
              $temp_element = $output[$data['tag']];
              $output[$data['tag']] = array();
              $output[$data['tag']][] = $temp_element;
              $output[$data['tag']][] = $element;
            }
            // Already an array of elements.
            elseif (isset($output[$data['tag']]) && is_array($output[$data['tag']])) {
              $output[$data['tag']][] = $element;
            }
            // A single element.
            else {
              $output[$data['tag']] = $element;
            }
            break;
        }
      }
      else {
        $children[] = $data;
      }
    }

    return $output;
  }
}
