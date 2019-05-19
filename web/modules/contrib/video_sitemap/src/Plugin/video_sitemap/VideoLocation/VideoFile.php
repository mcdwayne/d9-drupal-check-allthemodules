<?php

namespace Drupal\video_sitemap\Plugin\video_sitemap\VideoLocation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\MediaInterface;
use Drupal\video_sitemap\VideoLocationPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A VideoFile location plugin (used with video file as a source).
 *
 * @VideoLocation(
 *   id = "video_file",
 *   title = @Translation("Video File (used with video file as a source)")
 * )
 */
class VideoFile extends VideoLocationPluginBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Create a plugin with the given input.
   *
   * @param array $configuration
   *   The configuration of the plugin.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getThumbnailLoc(MediaInterface $media) {
    $storage = $this->entityTypeManager->getStorage('file');
    $thumbnail = $media->get('thumbnail')->getValue();
    $target_id = !empty($thumbnail) ? $thumbnail[0]['target_id'] : FALSE;
    if (is_numeric($target_id)) {
      $file = $storage->load($target_id);
      if (!empty($file)) {
        return file_create_url($file->getFileUri());
      }
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPlayerLoc(MediaInterface $media) {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getContentLoc(MediaInterface $media) {
    $storage = $this->entityTypeManager->getStorage('file');
    $target_id = $this->getSourceFieldValue($media);
    if (is_numeric($target_id)) {
      $file = $storage->load($target_id);
      if (!empty($file)) {
        return file_create_url($file->getFileUri());
      }
    }
    return '';
  }

  /**
   * Get the primary value stored in the Media source field.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity..
   *
   * @return mixed
   *   The source value.
   *
   * @throws \RuntimeException
   *   If the source field for the media source is not defined.
   */
  protected function getSourceFieldValue(MediaInterface $media) {
    $source = $media->getSource();
    return $source->getSourceFieldValue($media);
  }

}
