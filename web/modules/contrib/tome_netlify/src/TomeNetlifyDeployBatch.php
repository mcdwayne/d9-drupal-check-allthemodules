<?php

namespace Drupal\tome_netlify;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\tome_base\PathTrait;
use Drupal\tome_static\StaticGeneratorInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/**
 * A service to wrap batch operations for deploying to Netlify.
 */
class TomeNetlifyDeployBatch {

  use PathTrait;
  use DependencySerializationTrait;

  /**
   * The static generator.
   *
   * @var \Drupal\tome_static\StaticGeneratorInterface
   */
  protected $static;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * TomeNetlifyDeployBatch constructor.
   *
   * @param \Drupal\tome_static\StaticGeneratorInterface $static
   *   The static generator.
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(StaticGeneratorInterface $static, Client $http_client, ConfigFactoryInterface $config_factory) {
    $this->static = $static;
    $this->httpClient = $http_client;
    $this->config = $config_factory->get('tome_netlify.settings');
  }

  /**
   * Determines if the site is configured to properly deploy.
   *
   * @return bool
   *   Whether or not the site is configured to properly deploy.
   */
  public function checkConfiguration() {
    return !empty($this->config->get('access_token')) && !empty($this->config->get('site_id'));
  }

  /**
   * Determines if a static build exists.
   *
   * @return bool
   *   Whether or not a static build exists.
   */
  public function checkStaticBuild() {
    return file_exists($this->static->getStaticDirectory());
  }

  /**
   * Gets a batch object for deploying to Netlify.
   *
   * @param string $title
   *   The deploy title.
   *
   * @return \Drupal\Core\Batch\BatchBuilder
   *   The batch builder.
   */
  public function getBatch($title) {
    $batch_builder = new BatchBuilder();
    $files = [];
    foreach (file_scan_directory($this->static->getStaticDirectory(), '/.*/') as $file) {
      $files[] = $file->uri;
    }
    foreach (array_chunk($files, 10) as $chunk) {
      $batch_builder->addOperation([$this, 'getHashes'], [$chunk]);
    }
    $batch_builder->addOperation([$this, 'deployRequiredFiles'], [$title]);
    return $batch_builder;
  }

  /**
   * Gets hashes for the given files.
   *
   * @param array $files
   *   An array of file URIs.
   * @param array|\ArrayAccess $context
   *   The batch context.
   */
  public function getHashes(array $files, &$context) {
    $file_hashes = [];
    foreach ($files as $file) {
      $file_path = str_replace($this->static->getStaticDirectory(), '', $file);
      $file_hashes[$file_path] = sha1_file($file);
    }
    $context['results']['files'] = isset($context['results']['files']) ? $context['results']['files'] : [];
    $context['results']['files'] = array_merge($context['results']['files'], $file_hashes);
  }

  /**
   * Finds what files are required and sets a new batch to deploy them.
   *
   * @param string $title
   *   A title for the deploy.
   * @param array|\ArrayAccess $context
   *   The batch context.
   *
   * @throws \Exception
   */
  public function deployRequiredFiles($title, &$context) {
    $file_hashes = $context['results']['files'];
    $content = json_encode([
      'files' => $file_hashes,
      'draft' => TRUE,
    ]);
    $query = [
      'title' => $title,
    ];
    try {
      $response = $this->netlifyRequest($this->joinPaths('/sites/', $this->config->get('site_id'), '/deploys'), 'POST', $query, 'application/json', $content);
    }
    catch (\Exception $e) {
      $context['results']['errors'][] = 'Netlify deploy creation failed. Error message: ' . $e->getMessage();
      return;
    }
    $return = json_decode($response->getBody(), TRUE);
    if (is_array($return) && isset($return['required']) && isset($return['id'])) {
      $context['results']['deploy_ssl_url'] = $return['deploy_ssl_url'];
      $context['results']['admin_url'] = $return['admin_url'];
      $required_files = array_values(array_flip(array_intersect($file_hashes, $return['required'])));
      $batch_builder = new BatchBuilder();
      foreach (array_chunk($required_files, 5) as $chunk) {
        $batch_builder->addOperation([$this, 'deployFiles'], [$chunk, $return['id']]);
      }
      batch_set($batch_builder->toArray());
    }
    else {
      $context['results']['errors'][] = 'Unable to parse JSON response from Netlify.';
    }
  }

  /**
   * Deploys the given files.
   *
   * @param array $files
   *   A files to deploy.
   * @param string $deploy_id
   *   The Netlify deploy ID.
   */
  public function deployFiles(array $files, $deploy_id) {
    if (!empty($context['results']['errors'])) {
      return;
    }
    foreach ($files as $file) {
      $contents = file_get_contents($this->joinPaths($this->static->getStaticDirectory(), $file));
      try {
        $this->netlifyRequest($this->joinPaths('/deploys/', $deploy_id, '/files/', $file), 'PUT', [], 'application/octet-stream', $contents);
      }
      catch (\Exception $e) {
        $context['results']['errors'][] = 'Netlify file upload failed. Error message: ' . $e->getMessage();
        return;
      }
    }
  }

  /**
   * Makes a request to Netlify.
   *
   * @param string $path
   *   The path to request.
   * @param string $method
   *   The request method. Defaults to GET.
   * @param array $query
   *   Query parameters for the request.
   * @param string $contentType
   *   The request Content-Type header value.
   * @param string $content
   *   The request content.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   A response.
   */
  protected function netlifyRequest($path, $method = 'GET', array $query = NULL, $contentType = NULL, $content = NULL) {
    $uri = $this->joinPaths('https://api.netlify.com/api/v1/', $path);
    $options = [];
    if ($contentType && $content) {
      $options[RequestOptions::HEADERS] = [
        'Content-Type' => $contentType,
      ];
      $options[RequestOptions::BODY] = $content;
    }
    if ($query) {
      $options[RequestOptions::QUERY] = $query;
    }
    $options[RequestOptions::QUERY]['access_token'] = $this->config->get('access_token');
    return $this->httpClient->request($method, $uri, $options);
  }

}
