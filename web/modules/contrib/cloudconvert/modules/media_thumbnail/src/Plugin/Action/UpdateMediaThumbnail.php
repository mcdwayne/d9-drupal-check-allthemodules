<?php

namespace Drupal\cloudconvert_media_thumbnail\Plugin\Action;

use Drupal\cloudconvert_media_thumbnail\MediaThumbnailManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
 *   id = "cloudconvert_update_media_thumbnail",
 *   label = @Translation("Update Media Thumbnail"),
 *   type = "media",
 *   confirm = TRUE,
 * )
 */
class UpdateMediaThumbnail extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface {

  /**
   * Media Thumbnail Manager.
   *
   * @var \Drupal\cloudconvert_media_thumbnail\MediaThumbnailManagerInterface
   */
  protected $mediaThumbnailManager;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\cloudconvert_media_thumbnail\MediaThumbnailManagerInterface $mediaThumbnailManager
   *   Media Thumbnail Manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MediaThumbnailManagerInterface $mediaThumbnailManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mediaThumbnailManager = $mediaThumbnailManager;
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
      $container->get('cloudconvert_media_thumbnail.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->mediaThumbnailManager->queueThumbnailTask($entity);
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

    return TRUE;
  }

}
