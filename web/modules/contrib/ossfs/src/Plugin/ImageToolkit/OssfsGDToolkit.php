<?php

namespace Drupal\ossfs\Plugin\ImageToolkit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\ossfs\OssfsStorageInterface;
use Drupal\system\Plugin\ImageToolkit\GDToolkit;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * GD2 image toolkit optimized for OSS File System, which replaces the system
 * default GD toolkit.
 *
 * @see ossfs_image_toolkit_alter().
 */
class OssfsGDToolkit extends GDToolkit {

  /**
   * The ossfs storage.
   *
   * @var \Drupal\ossfs\OssfsStorageInterface
   */
  protected $ossfsStorage;

  /**
   * Constructs a OssfsGDToolkit object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\ImageToolkit\ImageToolkitOperationManagerInterface $operation_manager
   *   The toolkit operation manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The StreamWrapper manager.
   * @param \Drupal\ossfs\OssfsStorageInterface $ossfs_storage
   *   The ossfs storage.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ImageToolkitOperationManagerInterface $operation_manager, LoggerInterface $logger, ConfigFactoryInterface $config_factory, StreamWrapperManagerInterface $stream_wrapper_manager, OssfsStorageInterface $ossfs_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $operation_manager, $logger, $config_factory, $stream_wrapper_manager);
    $this->ossfsStorage = $ossfs_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('image.toolkit.operation.manager'),
      $container->get('logger.channel.image'),
      $container->get('config.factory'),
      $container->get('stream_wrapper_manager'),
      $container->get('ossfs.storage')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Avoid downloading the remote image for image style preview.
   *
   * @see \Drupal\image\Plugin\Field\FieldWidget\ImageWidget::process()#177
   * @see \Drupal\Core\Image\Image::__construct()#53
   */
  public function parseFile() {
    if (strpos($this->getSource(), 'oss://') === 0) {
      $metadata = $this->ossfsStorage->read($this->getSource());
      if ($metadata && ($imagesize = $metadata['imagesize'])) {
        $data = explode(',', $imagesize);
        array_walk($data, function (&$value) {
          $value = (int) $value;
        });

        if (in_array($data[2], static::supportedTypes())) {
          $this->setType($data[2]);
          // Only width, height and image type are populated, the [3], 'mime',
          // 'channels' and 'bits' elements are not required, so omit them.
          $this->preLoadInfo = $data;
          return TRUE;
        }
      }
    }

    // Have parent::parseFile() download the remote file to get image size.
    return parent::parseFile();
  }

}
