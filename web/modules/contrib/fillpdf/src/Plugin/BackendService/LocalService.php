<?php

namespace Drupal\fillpdf\Plugin\BackendService;

use Drupal\Core\File\FileSystem;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\fillpdf\FieldMapping\ImageFieldMapping;
use Drupal\fillpdf\FieldMapping\TextFieldMapping;
use Drupal\fillpdf\Plugin\BackendServiceBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @BackendService(
 *   id = "local_service",
 *   label = @Translation("FillPDF LocalServer")
 * )
 */
class LocalService extends BackendServiceBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The configuration.
   *
   * @var array
   */
  protected $configuration;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The Guzzle http client.
   *
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system.
   * @param \GuzzleHttp\Client $http_client
   *   The Guzzle http client.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(FileSystem $file_system, Client $http_client, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configuration = $configuration;
    $this->fileSystem = $file_system;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($container->get('file_system'), $container->get('http_client'), $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function parse($pdf_content) {
    $request = [
      'pdf' => base64_encode($pdf_content),
    ];

    $json = \GuzzleHttp\json_encode($request);

    $fields = [];

    try {
      $fields_response = $this->httpClient->post($this->configuration['local_service_endpoint'] . '/api/v1/parse', [
        'body' => $json,
        'headers' => ['Content-Type' => 'application/json'],
      ]);
    }
    catch (RequestException $request_exception) {
      if ($response = $request_exception->getResponse()) {
        \Drupal::messenger()->addError($this->t('Error %code. Reason: %reason.', [
          '%code' => $response->getStatusCode(),
          '%reason' => $response->getReasonPhrase(),
        ]));
      }
      else {
        \Drupal::messenger()->addError($this->t('Unknown error occurred parsing PDF.'));
      }
    }

    $fields = \GuzzleHttp\json_decode((string) $fields_response->getBody(), TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function merge($pdf_content, array $field_mappings, array $context) {
    $flatten = $context['flatten'];

    $api_fields = [];
    foreach ($field_mappings as $key => $mapping) {
      $api_field = NULL;

      if ($mapping instanceof TextFieldMapping) {
        $api_field = [
          'type' => 'text',
          'data' => $mapping->getData(),
        ];
      }
      elseif ($mapping instanceof ImageFieldMapping) {
        $api_field = [
          'type' => 'image',
          'data' => base64_encode($mapping->getData()),
        ];

        if ($extension = $mapping->getExtension()) {
          $api_field['extension'] = $extension;
        }
      }

      if ($api_field) {
        $api_fields[$key] = $api_field;
      }
    }

    $request = [
      'pdf' => base64_encode($pdf_content),
      'flatten' => $flatten,
      'fields' => $api_fields,
    ];

    $json = \GuzzleHttp\json_encode($request);

    try {
      $response = $this->httpClient->post($this->configuration['local_service_endpoint'] . '/api/v1/merge', [
        'body' => $json,
        'headers' => ['Content-Type' => 'application/json'],
      ]);

      $decoded = \GuzzleHttp\json_decode((string) $response->getBody(), TRUE);
      return base64_decode($decoded['pdf']);
    }
    catch (RequestException $e) {
      watchdog_exception('fillpdf', $e);
      return NULL;
    }
  }

}
