<?php

namespace Drupal\wizenoze\Helper;

use GuzzleHttp\json_decode;

/**
 * WizenozeAPI class.
 *
 * This class will interact with Wizenoze server for following interactions.
 *
 * Content Ingestion,Content Search are signleton class.
 *
 * @param authorization (Key for connection)
 *   Parameter authorization.
 */
class WizenozeAPI {

  /**
   * Keep class object.
   *
   * @var object
   */
  public static $wizeNoze = NULL;

  /**
   * Keep authorization code for API connection.
   *
   * @var string
   */
  private $authorization = NULL;

  /**
   * Keep Custom Search Engine Id for API connection.
   *
   * @var int
   */
  private $customSearchEngineId = NULL;

  /**
   * A readabilityLevel is the difficulty of a text.
   *
   * For Dutch we distinguish 5 readability levels.
   *
   * @var int
   */
  private $readabilityLevel = NULL;

  /**
   * Only contents suitable for that age group is returned.
   *
   * Even if the reading level is set to the hardest level.
   *
   * @var int
   */
  private $age = NULL;

  /**
   * Wizenoze API URL.
   *
   * @var string
   */
  private $apiURL = 'https://api.wizenoze.com/v1/customSearchEngines/{CustomSearchEngineId}/search?';

  /**
   * URL for Collections.
   *
   * @var string
   */
  private $apiCollectionURL = 'https://api.wizenoze.com/v1/collections';

  /**
   * URL for Custom Search Engine.
   *
   * @var string
   */
  private $apiCustomSeachEngineURL = 'https://api.wizenoze.com/v1/customSearchEngines';

  /**
   * URL for Content ingestion.
   *
   * @var string
   */
  private $apiContentStoreDocument = "https://api.wizenoze.com/v1/contentStoreDocument";

  /**
   * Collection types.
   *
   * @var array
   */
  public static $wizenozeCollectionTypes = [
    'privateCollection',
    'paidCollection',
    'publicCollection',
  ];

  /**
   * Keep All collections.
   *
   * @var array
   */
  private static $collectionList = [];

  /**
   * Keep All shared collections.
   *
   * @var array
   */
  private static $sharedCollectionList = [];

  /**
   * The config object settings.
   *
   * @var config
   */
  private $config;

  /**
   * Private constructor to avoid instantiation.
   */
  public function __construct() {
    $this->config = \Drupal::config('wizenoze.settings');
  }

  /**
   * Get class instance using this function.
   *
   * @return WizenozeAPI
   *   Return string.
   */
  public static function getInstance() {
    if (!self::$wizeNoze) {
      self::$wizeNoze = new WizenozeAPI();
    }
    self::$wizeNoze->init();
    return self::$wizeNoze;
  }

  /**
   * Implement custom function.
   */
  public function init() {
    $this->setAuthorization();
    $this->setCustomSearchEngineId();
    $this->setAge();
    $this->setReadabilityLevel();
  }

  /**
   * Set age param.
   *
   * @param int $age
   *   Parameter age.
   */
  public function setAge($age = NULL) {
    if ($age == NULL) {
      $age = $this->config->get('age');
    }
    $this->age = $age;
  }

  /**
   * Get age param.
   *
   * @return int
   *   Return int.
   */
  public function getAge() {
    return $this->age;
  }

  /**
   * Set ReadabilityLevel param.
   *
   * @param int $readabilityLevel
   *   Parameter readabilityLevel.
   */
  public function setReadabilityLevel($readabilityLevel = NULL) {
    if ($readabilityLevel == NULL) {
      $readabilityLevel = $this->config->get('readabilityLevel');
    }
    $this->readabilityLevel = $readabilityLevel;
  }

  /**
   * Custom function getReadabilityLevel.
   */
  public function getReadabilityLevel() {
    return $this->readabilityLevel;
  }

  /**
   * Set API Authorization Key.
   *
   * @param string $authorization
   *   Parameter authorization.
   *
   * @throws Exception
   */
  public function setAuthorization($authorization = NULL) {
    if ($authorization == NULL) {
      $authorization = $this->config->get('authorization');
      if (empty($authorization)) {
        throw new \Exception(t('Authorization key not found', '1001'));
      }
    }
    $this->authorization = $authorization;
  }

  /**
   * Return API Authorization Key.
   */
  public function getAuthorization() {
    return $this->authorization;
  }

  /**
   * Set API CustomSearchEngineId.
   *
   * @param int $customSearchEngineId
   *   Parameter customSearchEngineId.
   *
   * @throws Exception
   */
  public function setCustomSearchEngineId($customSearchEngineId = NULL) {
    if ($customSearchEngineId != NULL) {
      if (!is_numeric($customSearchEngineId)) {
        throw new \Exception('Custom Search Engine Id should be numeric', '1003');
      }
    }
    $this->customSearchEngineId = $customSearchEngineId;
  }

  /**
   * Return API CustomSearchEngineId Key.
   */
  public function getCustomSearchEngineId() {
    return $this->customSearchEngineId;
  }

  /**
   * Send search reqeust based on param.
   *
   * @param array $param
   *   Parameter param.
   */
  public function query(array $param) {
    // Sanitize Query String.
    $q = $this->sanitize($param['q']);
    $condition[] = 'q=' . urlencode($q);

    // Set age condition.
    if ($this->age > 0) {
      $condition[] = 'age=' . $this->age;
    }
    // Set readability condition.
    if ($this->readabilityLevel) {
      $condition[] = 'readabilityLevel=' . $this->readabilityLevel;
    }

    // Set Page limit.
    if (isset($param['startPage']) && is_numeric($param['startPage'])) {
      $condition[] = 'startPage=' . $param['startPage'];
    }

    if (isset($param['pageSize']) && is_numeric($param['pageSize'])) {
      $condition[] = 'pageSize=' . $param['pageSize'];
    }

    // Combine all search params.
    $this->apiURL = $this->apiURL . implode('&', $condition);
    $this->apiURL = str_replace('{CustomSearchEngineId}', $this->getCustomSearchEngineId(), $this->apiURL);
    return $this;
  }

  /**
   * Input sanitization.
   *
   * @param string $q
   *   Parameter q.
   *
   * @return string
   *   Return string.
   */
  public function sanitize($q) {
    return $q;
  }

  /**
   * Connect to search API for result.
   *
   * @param string $url
   *   Parameter url.
   *
   * @throws \Exception
   *
   * @return mixed
   *   Return string.
   */
  public function execute($url = NULL) {
    $url = ($url == NULL) ? $this->apiURL : $url;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $this->getAuthorization()]);
    $result = curl_exec($ch);
    if ($result === FALSE) {
      throw new \Exception(curl_error($ch), '1004');
    }
    return $result;
  }

  /**
   * Post request for Wizenoze.
   *
   * @param array $param
   *   Parameter.
   *
   * @throws \Exception
   *
   * @return mixed
   *   Return string.
   */
  public function postRequest(array $param) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $param['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    if (isset($param['CURLOPT_CUSTOMREQUEST'])) {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $param['CURLOPT_CUSTOMREQUEST']);
    }
    else {
      curl_setopt($ch, CURLOPT_POST, TRUE);
    }
    if (isset($param['postField'])) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param['postField']));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      "Content-Type: application/json",
      "Authorization: " . $this->getAuthorization(),
    ]);

    $result = curl_exec($ch);
    if ($result === FALSE) {
      throw new \Exception(curl_error($ch), '1004');
    }
    return $result;
  }

  /**
   * Create Collection at Wizenoze.
   *
   * @param array $param
   *   Parameter.
   *
   * @throws \Exception
   */
  public function createCollection(array $param) {
    // API url for collection creation.
    $param['url'] = $this->apiCollectionURL;
    // Name should be passed as param.
    if (empty($param['name'])) {
      throw new \Exception('Collection name cannot be empty', '1008');
    }

    // Validate accessType.
    if (!in_array($param['accessType'], self::$wizenozeCollectionTypes)) {
      throw new \Exception('Collection access type is invalid', '1009');
    }

    $param['postField'] = [
      'name' => $param['name'],
      'description' => $param['description'],
      'sourceName' => strtolower(str_replace(' ', '_', $param['name'])),
      'accessType' => $param['accessType'],
    ];

    // Post API request.
    return $this->postRequest($param);
  }

  /**
   * Create Collection at Wizenoze.
   *
   * @param array $param
   *   Parameter.
   *
   * @throws \Exception
   */
  public function updateCollection(array $param) {
    // API url for collection creation.
    $param['url'] = $this->apiCollectionURL . '/' . $param['id'];
    // Name should be passed as param.
    if (empty($param['name'])) {
      throw new \Exception('Collection name cannot be empty', '1008');
    }
    // Validate accessType.
    if (!in_array($param['accessType'], self::$wizenozeCollectionTypes)) {
      throw new \Exception('Collection access type is invalid', '1009');
    }

    $param['postField'] = [
      'name' => $param['name'],
      'description' => $param['description'],
      'sourceName' => strtolower(str_replace(' ', '_', $param['name'])),
      'accessType' => $param['accessType'],
    ];

    $param['CURLOPT_CUSTOMREQUEST'] = 'PUT';

    // Post API request.
    return $this->postRequest($param);
  }

  /**
   * Get Colleciton list.
   */
  public function collectionList() {
    if (empty(self::$collectionList)) {
      $result = json_decode($this->execute($this->apiCollectionURL . '?expanded=true'), TRUE);
      if ($result['status'] == 'success') {
        self::$collectionList = $result['collections'];
      }
    }
    return self::$collectionList;
  }

  /**
   * Get Shared Colleciton list.
   */
  public function sharedCollectionList() {
    if (empty(self::$sharedCollectionList)) {
      $result = json_decode($this->execute($this->apiCollectionURL . '/shared'), TRUE);
      if ($result['status'] == 'success') {
        self::$sharedCollectionList = $result['collections'];
      }
    }
    return self::$sharedCollectionList;
  }

  /**
   * Get Colleciton list.
   *
   * @param int $id
   *   Id.
   *
   * @return string
   *   String result.
   */
  public function collectionName($id) {
    $list = $this->collectionList();
    $name = NULL;
    foreach ($list as $item) {
      if ($item['id'] == $id) {
        $name = $item['name'];
        break;
      }
    }
    if ($name == NULL) {
      $item = $this->viewCollection($id);
      $name = $item['name'];
    }
    return $name;
  }

  /**
   * Get Colleciton list.
   *
   * @param int $id
   *   Id.
   *
   * @return string
   *   String result.
   */
  public function collectionSourceName($id) {
    $list = $this->collectionList();
    $name = NULL;
    foreach ($list as $item) {
      if ($item['id'] == $id) {
        $name = $item['sourceName'];
        break;
      }
    }
    if ($name == NULL) {
      $item = $this->viewCollection($id);
      $name = $item['sourceName'];
    }
    return $name;
  }

  /**
   * Get Colleciton list.
   *
   * @param int $id
   *   Id.
   */
  public function viewCollection($id) {
    $result = json_decode($this->execute($this->apiCollectionURL . '/' . $id), TRUE);
    if ($result['status'] == 'success') {
      return $result['collection'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function searchEngineList() {
    $result = json_decode($this->execute($this->apiCustomSeachEngineURL), TRUE);
    return $result['customSearchEngines'];
  }

  /**
   * Get Colleciton list.
   *
   * @param int $id
   *   The id.
   */
  public function viewSearchEngine($id) {
    $result = json_decode($this->execute($this->apiCustomSeachEngineURL . '/' . $id), TRUE);
    return $result['customSearchEngine'];
  }

  /**
   * Create Search engine.
   *
   * @param array $param
   *   Array of parameters.
   *
   * @throws \Exception
   *   Exception.
   *
   * @return array
   *   The return array of mixed variable.
   */
  public function createSearchEngine(array $param) {
    // API url for collection creation.
    $param['url'] = $this->apiCustomSeachEngineURL;
    // Name should be passed as param.
    if (empty($param['name'])) {
      throw new \Exception('Search engine name cannot be empty', '1009');
    }
    // Setting default language 90.
    $param['postField'] = [
      'name' => $param['name'],
      'description' => $param['description'],
      'languageId' => 29,
      'active' => ($param['status']) ? TRUE : FALSE,
    ];

    // Post API request.
    return $this->postRequest($param);
  }

  /**
   * Create Search engine.
   *
   * @param array $param
   *   Array of parameters.
   *
   * @throws \Exception
   *   Exception.
   *
   * @return array
   *   The return array of mixed variable.
   */
  public function updateSearchEngine(array $param) {

    // API url for collection creation.
    $param['url'] = $this->apiCustomSeachEngineURL . '/' . $param['id'];
    // Name should be passed as param.
    if (empty($param['name'])) {
      throw new \Exception('Search engine name cannot be empty', '1009');
    }
    // Setting default language english (29), 90 for Dutch.
    $param['postField'] = [
      'name' => $param['name'],
      'description' => $param['description'],
      'languageId' => 29,
      'active' => ($param['status']) ? TRUE : FALSE,
    ];

    $param['CURLOPT_CUSTOMREQUEST'] = 'PUT';

    // Post API request.
    return $this->postRequest($param);
  }

  /**
   * Push Source.
   *
   * @param int $id
   *   The Id.
   * @param array $sources
   *   Sources array.
   * @param array $newSource
   *   new Source array.
   */
  public function updateSearchEngineSources($id, array $sources, array $newSource) {
    $sourcesList = [];
    foreach ($sources as $source) {
      if ($source['sourceType'] == 'Collection') {
        $sourcesList[] = $source['sourceId'];
      } //['sourceType' => 'Collection', 'sourceId' => $source ];
    }

    $sourecInt = array_intersect($sourcesList, $newSource);

    // Remove source.
    foreach ($sources as $source) {
      if ($source['sourceType'] == 'Collection') {
        if (!in_array($source['sourceId'], $sourecInt)) {
          $this->postRequest([
            'url' => $this->apiCustomSeachEngineURL . '/' . $id . '/sources',
            'CURLOPT_CUSTOMREQUEST' => 'DELETE',
            'postField' => ['sourceType' => 'Collection', 'sourceId' => $source['sourceId']],
          ]);
        }
      }
    }

    // .Add source.
    foreach ($newSource as $source) {
      if (!in_array($source, $sourecInt)) {
        $this->postRequest([
          'url' => $this->apiCustomSeachEngineURL . '/' . $id . '/sources',
          'CURLOPT_CUSTOMREQUEST' => 'PUT',
          'postField' => ['sourceType' => 'Collection', 'sourceId' => $source],
        ]);
      }
    }
  }

  /**
   * Function for content ingestion.
   *
   * @param array $content
   *   Array of content.
   */
  public function ingestDocument(array $content) {

    // Content Ingestion URL.
    $param['url'] = $this->apiContentStoreDocument;
    // Post param for content ingestion.
    $param['postField'] = [
      'document' => [
        'sourceName' => $content['sourceName'],
        'id' => $content['id'],
        'languageCode' => 'en',
        'title' => $content['title'],
        'content' => $this->getContent($content['body']),
        'url' => $content['url'],
      ],
    ];

    return $this->postRequest($param);
  }

  /**
   * Body of document.
   *
   * @param mixed $body
   *   Data.
   */
  public function getContent($body) {
    $content = [];
    if (is_array($body)) {
      foreach ($body as $val) {
        $content[] = ['text' => $val];
      }
    }
    else {
      $content[] = ['text' => $body];
    }
    return $content;
  }

  /**
   * Delete the ingested document.
   *
   * @param string $sourceName
   *   The source Name.
   * @param mixed $documentId
   *   The id.
   */
  public function deleteDocument($sourceName, $documentId) {
    return $this->postRequest([
      'url' => $this->apiContentStoreDocument . '?sourceName=' . $sourceName . '&documentId=' . $documentId,
      'CURLOPT_CUSTOMREQUEST' => 'DELETE',
    ]);
  }

}
