<?php

namespace Drupal\search_api_swiftype\SwiftypeClient;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\search_api_swiftype\Exception\DocumentTypeNotFoundException;
use Drupal\search_api_swiftype\Exception\EngineNotFoundException;
use Drupal\search_api_swiftype\Exception\SwiftypeException;
use Drupal\search_api_swiftype\SwiftypeDocumentType\SwiftypeDocumentTypeInterface;
use Drupal\search_api_swiftype\SwiftypeEngine\SwiftypeEngineInterface;
use Drupal\search_api_swiftype\SwiftypeEntityFactoryInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Basic service for Swiftype client communication.
 */
class SwiftypeClientService implements SwiftypeClientInterface {

  /**
   * The Swiftype entity factory.
   *
   * @var \Drupal\search_api_swiftype\SwiftypeEntityFactoryInterface
   */
  protected $entityFactory;

  /**
   * The HTTP client used fore requests to Swiftype.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The endpoint URL.
   *
   * @var string
   */
  protected $endpoint = 'https://api.swiftype.com/api/v1/';

  /**
   * The key used to authenticate all API requests.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * Constructs a SwiftypeClientService.
   *
   * @param \Drupal\search_api_swiftype\SwiftypeEntityFactoryInterface $entity_factory
   *   The Swiftype entity factory.
   * @param \Drupal\Core\Http\ClientFactory $client_factory
   *   The client factory to build http clients.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(SwiftypeEntityFactoryInterface $entity_factory, ClientFactory $client_factory, CacheBackendInterface $cache) {
    $this->entityFactory = $entity_factory;
    $this->client = $client_factory->fromOptions([
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'base_uri' => $this->endpoint,
    ]);
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function setApiKey($key) {
    $this->apiKey = $key;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFactory() {
    return $this->entityFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthorized() {
    try {
      // Test listing all engines and get the raw response.
      $response = $this->apiGet('engines.json', [], FALSE);
    }
    catch (ClientException $ex) {
      return FALSE;
    }
    return $response->getStatusCode() === 200;
  }

  /**
   * {@inheritdoc}
   */
  public function listEngines() {
    $cache_key = 'search_api_swiftype.engines';
    $cache_tags = [
      "search_api_swiftype:api:{$this->apiKey}",
      'search_api_swiftype:engines',
    ];
    $engine_data = [];
    $engines = [];

    if ($cache = $this->cache->get($cache_key)) {
      $engine_data = $cache->data;
    }

    if (empty($engine_data)) {
      // Get list of engines from server.
      $engine_data = $this->apiGet('engines.json');
      // Cache list of engines.
      $this->cache->set($cache_key, $engine_data, CacheBackendInterface::CACHE_PERMANENT, $cache_tags);
    }
    foreach ($engine_data as $values) {
      $engines[$values['slug']] = $this->getEntityFactory()->createEngine($this, $values);
    }

    return $engines;
  }

  /**
   * {@inheritdoc}
   */
  public function createEngine($name) {
    $data = [
      'engine' => [
        'name' => $name,
      ],
    ];
    $response = $this->apiPost('engines.json', $data);
    if (isset($response['error'])) {
      throw new SwiftypeException($response['error']);
    }

    // Return the new engine.
    return $this->getEntityFactory()->createEngine($this, $response);
  }

  /**
   * {@inheritdoc}
   */
  public function getEngine($slug) {
    $engines = $this->listEngines();

    if (empty($engines[$slug])) {
      throw new EngineNotFoundException($this->t('An engine with the name @name does not exist.', ['@name' => $slug]));
    }

    // Return the found engine.
    return $engines[$slug];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEngine(SwiftypeEngineInterface $engine) {
    $response = $this->apiDelete("engines/{$engine->getSlug()}");
    if (empty($response['error'])) {
      return;
    }

    if (strpos($response['error'], 'Record not found') === 0) {
      throw new EngineNotFoundException($response['error']);
    }
    throw new SwiftypeException($response['error']);
  }

  /**
   * {@inheritdoc}
   */
  public function listDocumentTypes(SwiftypeEngineInterface $engine) {
    $cache_key = 'search_api_swiftype.document_types';
    $cache_tags = [
      "search_api_swiftype:api:{$this->apiKey}",
      "search_api_swiftype:engine:{$engine->getSlug()}",
      'search_api_swiftype:document_types',
      "search_api_swiftype:engine:{$engine->getSlug()}:document_types",
    ];
    $type_data = [];
    $types = [];

    if ($cache = $this->cache->get($cache_key)) {
      $type_data = $cache->data;
    }

    if (empty($type_data)) {
      // Get list of document types for the given engine from server.
      $type_data = $this->apiGet("engines/{$engine->getSlug()}/document_types.json");
      // Cache list of document types.
      $this->cache->set($cache_key, $type_data, CacheBackendInterface::CACHE_PERMANENT, $cache_tags);
    }
    foreach ($type_data as $values) {
      $types[$values['slug']] = $this->getEntityFactory()->createDocumentType($this, $values);
    }

    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function createDocumentType(SwiftypeEngineInterface $engine, $name) {
    $data = [
      'document_type' => [
        'name' => $name,
      ],
    ];
    $response = $this->apiPost("engines/{$engine->getSlug()}/document_types.json", $data);
    if (isset($response['error'])) {
      throw new SwiftypeException($response['error']);
    }

    // Return the new engine.
    return $this->getEntityFactory()->createDocumentType($this, $response);
  }

  /**
   * {@inheritdoc}
   */
  public function getDocumentType(SwiftypeEngineInterface $engine, $slug) {
    $document_types = $this->listDocumentTypes($engine);
    if (empty($document_types[$slug])) {
      throw new DocumentTypeNotFoundException($this->t('A document type with the name @name does not exist.', ['@name' => $slug]));
    }

    // Return the found document type.
    return $document_types[$slug];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDocumentType(SwiftypeEngineInterface $engine, $slug) {
    $response = $this->apiDelete("engines/{$engine->getSlug()}/document_types/{$slug}.json");
    if (empty($response['error'])) {
      return;
    }

    if (strpos($response['error'], 'Record not found') === 0) {
      throw new DocumentTypeNotFoundException($response['error']);
    }
    throw new SwiftypeException($response['error']);
  }

  /**
   * {@inheritdoc}
   */
  public function bulkCreateOrUpdateDocuments(SwiftypeEngineInterface $engine, SwiftypeDocumentTypeInterface $document_type, array $documents) {
    $url = "engines/{$engine->getSlug()}/document_types/{$document_type->getSlug()}/documents/bulk_create_or_update_verbose";
    $items = [];
    /** @var \Drupal\search_api_swiftype\SwiftypeDocument\SwiftypeDocumentInterface[] $documents */
    foreach ($documents as $document) {
      $items[] = [
        'external_id' => $document->getExternalId(),
        'fields' => array_values($document->getFields()),
      ];
    }
    $data = [
      'documents' => $items,
    ];

    $response = $this->apiPost($url, $data);
    if (isset($response['error'])) {
      // Something went really wrong here.
      throw new SwiftypeException($response['error']);
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function bulkDeleteDocuments(SwiftypeEngineInterface $engine, SwiftypeDocumentTypeInterface $document_type, array $document_ids) {
    $url = "engines/{$engine->getSlug()}/document_types/{$document_type->getSlug()}/documents/bulk_destroy";
    $data = [
      'documents' => $document_ids,
    ];

    $response = $this->apiPost($url, $data);
    if (isset($response['error'])) {
      // Something went really wrong here.
      throw new SwiftypeException($response['error']);
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function search(SwiftypeEngineInterface $engine, array $data = []) {
    $url = "engines/{$engine->getSlug()}/search.json";
    $response = $this->apiPost($url, $data);
    if (isset($response['error'])) {
      // Something went really wrong here.
      throw new SwiftypeException($response['error']);
    }
    return $response;
  }

  /**
   * Make a GET request to the configured API endpoint.
   *
   * @param string $url
   *   The request URL.
   * @param array $data
   *   (Optional) Additional data possibly necessary for the request.
   * @param bool $decode
   *   Wheter to decode the response or not.
   *
   * @return array|\Psr\Http\Message\ResponseInterface
   *   Reponse data as array or the request response if $decode is FALSE.
   */
  protected function apiGet($url, array $data = [], $decode = TRUE) {
    if (empty($data['auth_token'])) {
      $data['auth_token'] = $this->apiKey;
    }
    // Remove array indexes.
    $query = preg_replace('/%5B(?:[0-9]+)%5D=/', '%5B%5D=', http_build_query($data));
    $url .= '?' . $query;
    $response = $this->client->get($url);
    if ($decode) {
      return \GuzzleHttp\json_decode($response->getBody(), TRUE);
    }
    return $response;
  }

  /**
   * Make a POST request to the configured API endpoint.
   *
   * @param string $url
   *   The request URL.
   * @param array $data
   *   (Optional) Additional data possibly necessary for the request.
   * @param bool $decode
   *   Wheter to decode the response or not.
   *
   * @return array|\Psr\Http\Message\ResponseInterface
   *   Reponse data as array or the request response if $decode is FALSE.
   */
  protected function apiPost($url, array $data = [], $decode = TRUE) {
    if (empty($data['auth_token'])) {
      $data['auth_token'] = $this->apiKey;
    }
    $body = \GuzzleHttp\json_encode($data);
    $response = $this->client->post($url, ['body' => $body]);
    if ($decode) {
      return \GuzzleHttp\json_decode($response->getBody(), TRUE);
    }
    return $response;
  }

  /**
   * Make a DELETE request to the configured API endpoint.
   *
   * @param string $url
   *   The request URL.
   * @param array $data
   *   (Optional) Additional data possibly necessary for the request.
   * @param bool $decode
   *   Wheter to decode the response or not.
   *
   * @return array|\Psr\Http\Message\ResponseInterface
   *   Reponse data as array or the request response if $decode is FALSE.
   */
  protected function apiDelete($url, array $data = [], $decode = TRUE) {
    if (empty($data['auth_token'])) {
      $data['auth_token'] = $this->apiKey;
    }
    // Remove array indexes.
    $query = preg_replace('/%5B(?:[0-9]+)%5D=/', '%5B%5D=', http_build_query($data));
    $url .= '?' . $query;
    $response = $this->client->delete($url);
    if ($decode) {
      return \GuzzleHttp\json_decode($response, TRUE);
    }
    return $response;
  }

}
