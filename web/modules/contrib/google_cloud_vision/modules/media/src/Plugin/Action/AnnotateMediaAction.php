<?php

namespace Drupal\google_cloud_vision_media\Plugin\Action;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\google_cloud_vision_media\MediaManagerInterface;
use Drupal\media\MediaInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An example action covering most of the possible options.
 *
 * If type is left empty, action will be selectable for all
 * entity types.
 *
 * @Action(
 *   id = "google_cloud_vision_annotate",
 *   label = @Translation("Annotate Media"),
 *   type = "media",
 *   confirm = TRUE,
 * )
 */
class AnnotateMediaAction extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface {

  /**
   * Media Manager.
   *
   * @var \Drupal\google_cloud_vision_media\MediaManagerInterface
   */
  protected $mediaManager;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\google_cloud_vision_media\MediaManagerInterface $mediaManager
   *   The media manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MediaManagerInterface $mediaManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mediaManager = $mediaManager;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('google_cloud_vision.media_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $entity;
    $this->mediaManager->queueAnnotation($media);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object instanceof MediaInterface && $object->getEntityType() === 'media') {
      /** @var \Drupal\Core\Access\AccessResult $access */
      $access = $object->access('update', $account, TRUE);
      return $return_as_object ? $access : $access->isAllowed();
    }

    // Other entity types may have different
    // access methods and properties.
    return TRUE;
  }

}
