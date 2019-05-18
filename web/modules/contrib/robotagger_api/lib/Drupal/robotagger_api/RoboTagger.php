<?php

/**
 * @file
 *
 * Can be used to requests the RoboTagger-Webservice to analyse text or
 * to use some usefully static methods.
 *
 * @see robotagger_api_call_webservice().
 * @see http://robotagger.com
 */
namespace Drupal\robotagger_api;

use Drupal;
use PHPUnit_Util_XML;

class RoboTagger {

  /**
   *
   */
   protected $config = array();

  /**
   * Contains the response from drupal_http_request() and
   * is set by RoboTagger::__analyze().
   *
   * @var array
   */
  protected $response = array();

  /**
   * Contains the data which is set by RoboTagger::__format() and
   * returned by robotagger_api_call_webservice().
   *
   * @var array
   */
  public $data = array();

  public function __construct() {
    $this->config = config('robotagger_api.server');
  }

  /**
   * Analyzes the text and prepared the data.
   *
   * @param string $content
   *   The content which should be analyzed.
   * @param array $params
   *   The annotation types.
   * @param string $lang
   *   The langcode example de.
   * @param array $topics
   *   An array of topics.
   *
   * @see robotagger_api_call_webservice().
   *
   * @return The RoboTagger object.
   */
  public function analyze($content, $params, $lang, $topics = array()) {
    if (empty($content)) {
      return;
    }
    $this->__analyze($content, $params, $lang, $topics);
    $this->__format();
    return $this;
  }

  /**
   * The private methode which do the magic to request webservice.
   *
   * @param string $content
   * @param array $params
   * @param string $lang
   * @param array $topics
   *
   * @see RoboTagger::analyze().
   *
   * @return The RoboTagger object.
   */
  private function __analyze($content, $params, $lang, $topics) {
    if (!empty($topics)) {
      $topics = array_map('trim', $topics);
      $topics = implode('/', $topics);
    }
    else{
      $topics = '';
    }
    // prepare annotypes
    ob_start();
    foreach ($params as $annotype) include drupal_get_path('module', 'robotagger_api') . '/robotagger_api.annotype.xml.php';
    $annotypes = ob_get_contents();
    ob_end_clean();

    // prepare xml data
    $data = array(
      'api_key' => $this->config->get('api_key'),
      'lang' => $lang,
      'annotypes' => $annotypes,
      'content' => check_plain($content),
      'topic' => $topics
    );
    // generate xml
    ob_start();
    include drupal_get_path('module', 'robotagger_api') . '/robotagger_api.xml.php';
    $xml = ob_get_contents();
    ob_end_clean();
    try {
      // query
      $headers  = array('Content-Type' => 'application/x-www-form-urlencoded');
      $data_enc = http_build_query(array('rtXMLRequest' => $xml), '', '&');
      $request = Drupal::httpClient()->post(self::getHost(), $headers, $data_enc);
      $response = $request->send();
      $this->response = $response;
      // $this->response = drupal_http_request(self::getHost(), array('headers' => $headers, 'method' => 'POST', 'data' => $data_enc, 'max_redirects' => 3));
      // check for errors
      if ($this->response->getStatusCode() != 200) {
        self::logError($this->response->getStatusCode(), $this->response->getStatusCode());
        return;
      }
    } catch (Exception $err) {
      return;
    }
    return $this;
  }

  /**
   * Prepare the response data which is stored in RoboTagger::response.
   *
   * @return The RoboTagger object.
   */
  private function __format(){
    try {
      $result = array();
      $xml = PHPUnit_Util_XML::load($this->response->getBody(TRUE));
      $DOMElements = $xml->getElementsByTagName('annotations');
      foreach ($DOMElements as $DOMElement) {
        $DOMNodeLists = $DOMElement->getElementsByTagName('annotation');
        foreach ($DOMNodeLists as $DOMNodeList) {
          $anno_type = $DOMNodeList->getAttribute('annoType');
          $class = new \stdClass();
          $class->value = $DOMNodeList->getAttribute('stringVal');
          $class->occurences = $DOMNodeList->getAttribute('occurrences');
          $class->threshold = $DOMNodeList->getAttribute('threshold');
          $class->subtype = $DOMNodeList->getAttribute('subtype');
          $result[$anno_type][] = $class;
        }
      }
      $this->data = $result;
      return $this;
    }
    catch (Exception $e) {
      return $this;
    }
  }

  /**
   * Returns the stored uri from RoboTagger-Webservice.
   *
   * @see robotagger_api_settings_form
   *
   * @return string
   *   The stored uri from RoboTagger-Webservice.
   */
  public static function getHost() {
    return variable_get('robotaggerapi_server', 'http://ws.robotagger.com:8080/WebService');
  }

  /**
   * Log an error message via watchdog() and print it via drupal_set_message().
   *
   * @param int $code
   *   A integer code which is set by drupal_http_request().
   * @param string $error
   *   A error message which is set by drupal_http_request().
   */
  private static function logError($code, $error) {
    drupal_set_message(t('RoboTagger-API processing error: (@code - @error)', array('@code' => $code, '@error' => $error)), 'error', FALSE);
    watchdog('robotagger_api', 'RoboTagger-API processing error: (@code - @error)', array('@code' => $code, '@error' => $error), WATCHDOG_ERROR);
  }

  /**
   * Returns the error code and error message.
   * @see robotagger_api_call_webservice().
   */
  public function getError() {
    if (!empty($this->response->error)) {
      return array('errorcode' => $this->response->code, 'errormessage' => $this->response->error);
    }
    return;
  }

  /**
   * Checks that the api-key valid or not.
   *
   * @param string $key
   *   The api-key for the webservice.
   * @param string $host
   *   The host uri for the webservice.
   *
   * @see robotagger_api_validate_api_key(). Please use this function.
   * @see http://robotagger.com
   *
   * @return boolean
   *   A 0 if it fails or 1 if the api-key is valid.
   */
  public static function validateAPIKey($key, $host) {
    if (empty($host)) {
      $host = self::getHost();
    }
    $request = Drupal::httpClient()->get($host.'?check_apikey=' . $key);
    $response = $request->send();
    $data = $response->getBody(TRUE);
    if ($response->isError()) {
      drupal_set_message(t("Webservice-URL isn't correct or you may have problems with your internet connection"), 'error');
      self::logError($response->getStatusCode(), $response->error);
      return FALSE;
    }
    return $data;
  }

  /**
   * Returns an numeric array annotypes and subtypes of annotype.
   *
   * @param string $langcode
   *   One of the supported language. Ex. de.
   *
   * @return array
   *   An numeric array with annotypes and subtypes.
   */
  public static function getAnnotypesAndSubtypes($langcode) {
    $return = array();
    $annotypes = self::getAnnotypes($langcode);
    $subtypes = array();
    foreach ($annotypes as $annotype) {
      $key = trim(drupal_strtolower($annotype['name']));
      $annotypes_new[$key] = $annotype['name'];
      if (!empty($annotype['subtypes'])) {
        foreach ($annotype['subtypes'] as $subtype) {
          $subtypes[$key] = $subtype['values'];
        }
      }
    }
    return array($annotypes_new, $subtypes);
  }

  /**
   * Returns an array of annotations which than is used as toxonomy terms.
   *
   * @param string $langcode
   *   One of the supported languages. Ex. de.
   *
   * @see robotagger_api_get_annotype_names(). Please use this function.
   *
   * @return array $annotypes
   *   An numeric array which contains an array with the keys name and
   *   description of a annotation.
   */
  public static function getAnnotypeNames($langcode) {
    $annotypes = array();
    $robotagger_annotypes = self::getAnnotypes($langcode);
    foreach ($robotagger_annotypes as $robotagger_annotype) {
      $annotypes[] = array('name' => $robotagger_annotype['name'], 'description' => $robotagger_annotype['description']);
    }
    return $annotypes;
  }

  /**
   * Request the RoboTagger-Webservice to get a list of supported annotypes and
   * his subtypes.
   *
   * @param string $langcode
   *   One of the supported language. Ex. de.
   *
   * @see robotagger_api_get_annotypes() Please use this funtion.
   *
   * @return array $vocs
   *   An numeric array of annotationtypes with the keys name, description
   *   and subtypes. Which contains an array of subtypes which also has a name,
   *   description and an array of strings.
   */
  public static function getAnnotypes($langcode) {
    if (($cache = cache()->get('robotagger_api_vocs')) && !empty($cache->data)) {
      return $cache->data;
    }
    $request = Drupal::httpClient()->get(self::getHost() . '?get_annotypes=1');
    $response = $request->send();
    $data = $response->getBody(TRUE);
    if (!empty($response->error)) {
      self::logError($response->code, $response->error);
      return array();
    }
    $vocs = array();
    $DOMDocument = PHPUnit_Util_XML::load($data);
    $annotation_types = $DOMDocument->getElementsByTagName('annotation_type');
    foreach ($annotation_types as $i => $annotation_type) {
      $name = $annotation_type->getAttribute('name');
      $voc = array();
      $voc['name'] = trim($name);
      $descriptions = $annotation_type->getElementsByTagName('description');
      $voc['description'] = '';
      if (!empty($descriptions)) {
        $voc['description'] = $descriptions->item(0)->nodeValue;
      }
      $voc['subtypes'] = array();
      $annotation_subtypes = $annotation_type->getElementsByTagName('annotation_subtype');
      foreach ($annotation_subtypes as $annotation_subtype) {
        $subtype = array();
        if (is_object($annotation_subtype)) {
          $subtypename = $annotation_subtype->getAttribute('name');
          $subtype['name'] = trim($subtypename);
          $subtype_values = $annotation_subtype->getElementsByTagName('subtype_value');
          $descriptions = $annotation_subtype->getElementsByTagName('description');
          $subtype['description'] = '';
          if (!empty($descriptions)) {
            $subtype['description'] = $descriptions->item(0)->nodeValue;
          }
          $subtype['values'] = array();
          if (is_object($subtype_values)) {
            foreach ($subtype_values as $subtype_value) {
              $value = trim($subtype_value->nodeValue);
              $subtype['values'][drupal_strtolower($value)] = $value;
            }
          }
        }
        $voc['subtypes'][] = $subtype;
      }//foreach ($annotation_subtypes as $annotation_subtype) {
      $vocs[$voc['name']] = $voc;
    }//foreach ($annotation_types as $i => $annotation_type) {
    if (!empty($vocs)) {
      $expire = time() + 60*60*24*7;
      cache()->set('robotagger_api_vocs', $vocs, $expire);
    }
    return $vocs;
  }

  /**
   * Request the RoboTagger-Webservice and returns an numeric array of topics.
   *
   * @see http://robotagger.com
   *
   * @return array $topics
   *   An numeric array of topics.
   */
  public static function getTopics() {
    $request = Drupal::httpClient()->get(self::getHost() . '?get_topics');
    $response = $request->send();
    $data = $response->getBody(TRUE);
    if (!empty($response->error)) {
      self::logError($response->code, $response->error);
      return array();
    }
    $topics = array();
    foreach ($xml = PHPUnit_Util_XML::load($data)->find('topic') as $topic) {
      $topics[] = $topic->text();
    }
    return $topics;
  }
}
