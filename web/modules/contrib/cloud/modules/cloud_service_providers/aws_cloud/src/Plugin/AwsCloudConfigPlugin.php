<?php

namespace Drupal\aws_cloud\Plugin;

use Drupal\cloud\Plugin\CloudConfigPluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\Filesystem;

/**
 * AWS Cloud Config Plugin.
 */
class AwsCloudConfigPlugin extends PluginBase implements CloudConfigPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * File system object.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  private $fileSystem;

  /**
   * AwsCloudConfigPlugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Entity type manager.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The Messenger service.
   * @param \Drupal\Core\File\FileSystem $fileSystem
   *   The FileSystem object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, Messenger $messenger, FileSystem $fileSystem) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
    $this->fileSystem = $fileSystem;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('file_system')
    );
  }

  /**
   * Load all entities for a given entity type and bundle.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of Entity Interface.
   */
  public function loadConfigEntities() {
    return $this->entityTypeManager->getStorage($this->pluginDefinition['entity_type'])->loadByProperties(['type' => [$this->pluginDefinition['entity_bundle']]]);
  }

  /**
   * Load an array of credentials.
   *
   * @param string $cloud_context
   *   Cloud Cotext string.
   *
   * @return array
   *   Array of credentials.
   */
  public function loadCredentials($cloud_context) {
    /* @var \Drupal\cloud\Entity\CloudConfig $entity */
    $entity = $this->loadConfigEntity($cloud_context);
    $credentials = [];
    if ($entity != FALSE) {
      $assume_role = $entity->get('field_assume_role')->value;
      $use_instance_credentials = $entity->get('field_use_instance_credentials')->value;
      $credentials['assume_role'] = FALSE;
      $credentials['use_instance_credentials'] = FALSE;

      if (isset($assume_role) && $assume_role == TRUE) {
        $credentials['assume_role'] = TRUE;
        $credentials['role_arn'] = sprintf("arn:aws:iam::%s:role/%s", trim($entity->get('field_account_id')->value), trim($entity->get('field_iam_role')->value));
      }

      if (isset($use_instance_credentials) && $use_instance_credentials == TRUE) {
        $credentials['use_instance_credentials'] = TRUE;
      }

      $credentials['ini_file'] = $this->fileSystem->realpath(aws_cloud_ini_file_path($entity->get('cloud_context')->value));
      $credentials['region'] = $entity->get('field_region')->value;
      $credentials['version'] = $entity->get('field_api_version')->value;
      $credentials['endpoint'] = $entity->get('field_api_endpoint_uri')->value;
    }
    return $credentials;
  }

  /**
   * Load a cloud config entity.
   *
   * @param string $cloud_context
   *   Cloud Cotext string.
   *
   * @return bool|mixed
   *   Entity or FALSE if there is no entity.
   */
  public function loadConfigEntity($cloud_context) {
    $entity = $this->entityTypeManager->getStorage($this->pluginDefinition['entity_type'])->loadByProperties(['cloud_context' => [$cloud_context]]);
    if (count($entity) == 1) {
      return array_shift($entity);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceCollectionTemplateName() {
    return 'view.aws_instances.page_1';
  }

  /**
   * {@inheritdoc}
   */
  public function getPricingPageRoute() {
    return 'aws_cloud.instance_type_prices';
  }

}
