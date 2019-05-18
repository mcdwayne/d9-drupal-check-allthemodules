<?php

/**
 * @file
 * Contains \Drupal\collect\FetchWebResource.
 */

namespace Drupal\collect;

use Drupal\collect\Entity\Container;
use Drupal\collect\Model\ModelManagerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

/**
 * Fetches a web resource into collect container.
 */
class FetchWebResource {

  /**
   * The default schema URI for web resource submissions.
   */
  const SCHEMA_URI = 'http://schema.md-systems.ch/collect/0.0.1/url';

  /**
   * The mime type of the submitted data.
   */
  const MIMETYPE = 'application/json';

  /**
   * The http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The injected model manager.
   *
   * @var \Drupal\collect\Model\ModelManagerInterface
   */
  protected $modelManager;

  /**
   * Set up a new FetchWebResource instance.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *    A Guzzle client object.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *    The entity manager service.
   * @param \Drupal\collect\Model\ModelManagerInterface $model_manager
   *    The injected model manager.
   */
  public function __construct(ClientInterface $http_client, EntityManagerInterface $entity_manager, ModelManagerInterface $model_manager) {
    $this->httpClient = $http_client;
    $this->entityManager = $entity_manager;
    $this->modelManager = $model_manager;
  }

  /**
   * Fetches a web resource as a collect container.
   *
   * @param string $url
   *   The web resource's URL.
   * @param string $accept_header
   *   The web resource's accept header.
   * @param string $schema_uri
   *   The schema URI.
   *
   * @return \Drupal\collect\CollectContainerInterface
   *   The new container containing the fetched web resource data.
   */
  public function fetch($url, $accept_header = '', $schema_uri = NULL) {
    if (!isset($schema_uri)) {
      $schema_uri = static::SCHEMA_URI;
    }

    // Create a new GET request.
    $request = new Request('GET', $url, [
      'Accept' => $accept_header,
      'Accept-Charset' => 'UTF-8, *'
    ]);
    // Get request headers.
    $request_headers = $request->getHeaders();

    try {
      // Send a request to the server and get a response.
      $response = $this->httpClient->send($request);
    }
    catch (RequestException $re) {
      if ($re->hasResponse()) {
        $exception_message = t('Oops! Web resource from :url has not been saved. Error code @status_code with message @error_message.', [
          '@status_code' => $re->getResponse()->getStatusCode(),
          '@error_message' => $re->getResponse()->getReasonPhrase(),
          ':url' => $url,
        ]);
      }
      else {
        $exception_message = t('Oops! Web resource from :url can not be reached.', [
          ':url' => $url,
        ]);
      }
      throw new RequestException($exception_message, $request);
    }
    // Get response headers.
    $response_headers = $response->getHeaders();
    // Get page content.
    $body = $response->getBody()->getContents();

    $data = [];
    $detected_charset = FALSE;
    if (isset($response_headers['Content-Type'])) {
      $content_type = $response_headers['Content-Type'][0];
      // Matching MIME type and Charset from the Content-Type header field.
      preg_match('@([\w/-]+)(;\s+charset=([^\s]+))?@i', $content_type, $matches);
      if (isset($matches[3])) {
        $detected_charset = TRUE;
        $charset = $matches[3];
        // Encode to UTF-8 if captured body content uses different charset.
        if (strtoupper($charset) != 'UTF-8') {
          $body = Unicode::convertToUtf8($body, $charset);
          $data['charset'] = $charset;
        }
      }

    }
    if (!$detected_charset) {
      $body = Unicode::convertToUtf8($body, 'ISO-8859-1');
      $data['charset'] = 'ISO-8859-1';
    }

    $data += [
      'request-headers' => $request_headers,
      'response-headers' => $response_headers,
      'body' => $body,
    ];

    $json_encoded_data = FALSE;
    if (Unicode::validateUtf8($data['body'])) {
      $json_encoded_data = Json::encode($data);
    }

    if (!$json_encoded_data) {
      throw new RequestException(t('Web resource from :url has not been saved. The JSON encoding failed. Content charset is invalid.', array(
        ':url' => $url,
      )), $request);
    }

    // Create a new container with fetched data.
    /* @var \Drupal\collect\Entity\Container $container */
    $container = Container::create(array(
      'origin_uri' => $url,
      'schema_uri' => $schema_uri,
      'type' => static::MIMETYPE,
      'data' => $json_encoded_data,
    ));

    /** @var \Drupal\collect\CollectStorage $container_storage */
    $container_storage = $this->entityManager->getStorage('collect_container');

    // Persist the container.
    return $container_storage->persist($container, $this->modelManager->isModelRevisionable($container));
  }

}
