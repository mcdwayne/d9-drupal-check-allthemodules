<?php

namespace Drupal\fillpdf\Plugin\FillPdfBackend;

use Drupal\Core\File\FileSystem;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\fillpdf\FieldMapping\ImageFieldMapping;
use Drupal\fillpdf\FieldMapping\TextFieldMapping;
use Drupal\fillpdf\FillPdfBackendPluginInterface;
use Drupal\fillpdf\FillPdfFormInterface;
use Drupal\fillpdf\Plugin\BackendServiceManager;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Plugin(
 *   id = "local_service",
 *   label = @Translation("FillPDF LocalServer"),
 *   description = @Translation("Network-accessible, self-installed PDF API. You will need a VPS or dedicated server."),
 *   weight = 5
 * )
 */
class LocalService implements FillPdfBackendPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The plugin's configuration.
   *
   * @var array
   */
  protected $configuration;

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The FillPDF backend service manager.
   *
   * @var \Drupal\fillpdf\Plugin\BackendServiceManager
   */
  protected $backendServiceManager;

  /**
   * The Guzzle http client.
   *
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * Constructs a LocalService plugin object.
   *
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system.
   * @param \GuzzleHttp\Client $http_client
   *   The Guzzle http client.
   * @param \Drupal\fillpdf\Plugin\BackendServiceManager $backend_service_manager
   *   The backend service manager.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(FileSystem $file_system, Client $http_client, BackendServiceManager $backend_service_manager, array $configuration, $plugin_id, $plugin_definition) {
    $this->fileSystem = $file_system;
    $this->httpClient = $http_client;
    $this->backendServiceManager = $backend_service_manager;
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('file_system'),
      $container->get('http_client'),
      $container->get('plugin.manager.fillpdf_backend_service'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function parse(FillPdfFormInterface $fillpdf_form) {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($fillpdf_form->file->target_id);
    $pdf = file_get_contents($file->getFileUri());

    /** @var \Drupal\fillpdf\Plugin\BackendServiceInterface $backend_service */
    $backend_service = $this->backendServiceManager->createInstance($this->pluginId, $this->configuration);

    return $backend_service->parse($pdf);
  }

  /**
   * {@inheritdoc}
   */
  public function populateWithFieldData(FillPdfFormInterface $fillpdf_form, array $field_mapping, array $context) {
    /** @var \Drupal\file\FileInterface $original_file */
    $original_file = File::load($fillpdf_form->file->target_id);
    $pdf = file_get_contents($original_file->getFileUri());

    // To use the BackendService, we need to convert the fields into the format
    // it expects.
    $mapping_objects = [];
    foreach ($field_mapping['fields'] as $key => $field) {
      if (substr($field, 0, 7) === '{image}') {
        // Remove {image} marker.
        $image_filepath = substr($field, 7);
        $image_realpath = $this->fileSystem->realpath($image_filepath);
        $mapping_objects[$key] = new ImageFieldMapping(file_get_contents($image_realpath), pathinfo($image_filepath, PATHINFO_EXTENSION));
      }
      else {
        $mapping_objects[$key] = new TextFieldMapping($field);
      }
    }

    /** @var \Drupal\fillpdf\Plugin\BackendServiceInterface $backend_service */
    $backend_service = $this->backendServiceManager->createInstance($this->pluginId, $this->configuration);

    return $backend_service->merge($pdf, $mapping_objects, $context);
  }

}
