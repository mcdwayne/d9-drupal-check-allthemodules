<?php

namespace Drupal\scenarios_contenthub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\acquia_contenthub\ContentHubCommonActions;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class ScenarioBuilderController.
 */
class ScenarioBuilderController extends ControllerBase {

  /**
   * Drupal\acquia_contenthub\ContentHubCommonActions definition.
   *
   * @var \Drupal\acquia_contenthub\ContentHubCommonActions
   */
  protected $acquiaContenthubCommonActions;
  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;
  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * Drupal\Core\Extension\ModuleInstallerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;
  /**
   * Drupal\Core\File\FileSystemInterface definition.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;
  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Entity\EntityManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new ScenarioBuilderController object.
   */
  public function __construct(ContentHubCommonActions $acquia_contenthub_common_actions, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, ModuleInstallerInterface $module_installer, FileSystemInterface $file_system, EntityTypeManagerInterface $entity_type_manager, EntityManagerInterface $entity_manager) {
    $this->acquiaContenthubCommonActions = $acquia_contenthub_common_actions;
    $this->loggerFactory = $logger_factory;
    $this->configFactory = $config_factory;
    $this->moduleInstaller = $module_installer;
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('acquia_contenthub_common_actions'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('module_installer'),
      $container->get('file_system'),
      $container->get('entity_type.manager'),
      $container->get('entity.manager')
    );
  }

  public function download() {
    $entities = [];

    // Get the current Scenario
    $scenarios_info = scenarios_info();
    $scenario = array_keys($scenarios_info)[0];

    // Loads all the supported entities from the site (YMMV).
    $types = ['user', 'media', 'block_content', 'node', 'menu_link_content'];
    // Loop the entity types.
    foreach ($types as $type) {
      $results = $this->entityTypeManager->getStorage($type)->loadByProperties([]);
      // Loop the results, adding them to the entity list.
      foreach ($results as $result) {
        $entities[] = $result;
      }
    }

    // Create the local CDF containing entities and their calculated dependencies.
    $document = $this->acquiaContenthubCommonActions->getLocalCdfDocument(...$entities);
    $host = str_replace('/', '\/',\Drupal::request()->getSchemeAndHttpHost());
    $json = str_replace($host, '%scenarios-replace-host%', $document->toString());
    $filepath = str_replace('/', '\/', PublicStream::basePath());
    $json = str_replace($filepath, '%scenarios-replace-filepath%', $json);

    // Save the json response to a file.
    $filename = $scenario . '.json';
    $file = file_save_data($json, 'public://' . $filename);

    // Load the file uri and provide a download response.
    $uri = $file->getFileUri();
    $headers = [
      'Content-Type'     => 'application/json',
      'Content-Disposition' => 'attachment;filename="' . $filename . '"'
    ];
    return new BinaryFileResponse($uri, 200, $headers, true);
  }

}
