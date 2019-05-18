<?php

namespace Drupal\media_imagick\Plugin\PluginDecorator;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\MediaInterface;
use Drupal\media_imagick\Plugin\MediaSourceDecoratorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Imagick image media source decorator.
 *
 * @PluginDecorator (
 *   id = "media_imagick",
 *   decorates = "Drupal\media\MediaSourceInterface"
 * )
 */
class Imagick extends MediaSourceDecoratorBase implements ContainerFactoryPluginInterface {

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
   * @return PluginInspectionInterface
   *   Returns an instance of this plugin, OR the undecorated one.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var PluginInspectionInterface $decorated */
    $decorated = $configuration['decorated'];
    if ($decorated->getPluginId() === 'image') {
      return new static($configuration, $plugin_id, $plugin_definition);
    }
    else {
      return $decorated;
    }
  }

  /**
   * The decorated Image source plugin.
   *
   * @var \Drupal\media\Plugin\media\Source\Image
   */
  protected $decorated;

  /**
   * A static cache for Imagick objects.
   *
   * @var \Imagick[]
   */
  protected $imagickCache;

  /**
   * Get Imagick object for a media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media object to get Imagick for. As this is expensive, it will be
   *   statically cached.
   *
   * @return \Imagick|bool
   *   The imagick object.
   */
  protected function getImagick(MediaInterface $media) {
    if (!class_exists('\Imagick')) {
      return FALSE;
    }
    $source_field = $this->decorated->getConfiguration()['source_field'];
    if (!$source_field) {
      return FALSE;
    }
    /** @var \Drupal\file\FileInterface $file */
    $file = $media->{$source_field}->entity;
    if (!$file) {
      return FALSE;
    }
    $uri = $file->getFileUri();
    $mtime = filemtime($uri);

    $imagick =& $this->imagickCache["$uri:$mtime"];
    if (!isset($imagick)) {
      try {
        $imagick = new \Imagick();
        $imagick->pingImage($uri);
      } catch (\Exception $e) {
        // Imagick can be very picky and throw "insufficient image data".
        $imagick = FALSE;
      }
    }
    return $imagick;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $attributes = $this->decorated->getMetadataAttributes();

    $attributes += [
      'media_imagick_format' => $this->t('Format (JPEG, PNG, etc.)'),
      'media_imagick_colorspace' => $this->t('Colorspace (RGB, CMYk, etc.)'),
      'media_imagick_icc' => $this->t('ICC color model'),
      'media_imagick_colorspace_icc' => $this->t('Colorspace/ICC color model'),
      'media_imagick_resolution' => $this->t('Resolution in DPI'),
      // Overridden from Image.
      'mime' => $this->t('Mimetype, from Imagick'),
      ($this->decorated)::METADATA_ATTRIBUTE_WIDTH => $this->t('Width, from Imagick'),
      ($this->decorated)::METADATA_ATTRIBUTE_HEIGHT => $this->t('Height, from Imagick'),
    ];

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    if ($imagick = $this->getImagick($media)) {
      switch ($name) {
        case 'media_imagick_format':
          return $imagick->getImageFormat();

        case 'media_imagick_colorspace':
          // Do it like this, as getImageColorspace() returns constants.
          $info = $imagick->identifyImage();
          $colorspace = isset($info['colorSpace']) ? $info['colorSpace'] : '?';
          return $colorspace;

        case 'media_imagick_icc':
          $properties = $imagick->getImageProperties();
          $icc = isset($properties['icc:model']) ? $properties['icc:model'] : '?';
          return $icc;

        case 'media_imagick_colorspace_icc':
          $info = $imagick->identifyImage();
          $colorspace = isset($info['colorSpace']) ? $info['colorSpace'] : '?';
          $properties = $imagick->getImageProperties();
          $icc = isset($properties['icc:model']) ? $properties['icc:model'] : '?';
          $colorspace_icc = "$colorspace/$icc";
          return $colorspace_icc;

        case 'media_imagick_resolution':
          $resolution = $imagick->getImageResolution();
          return (
            isset($resolution['x'])
            && isset($resolution['y'])
            && $resolution['x'] === $resolution['y']
          ) ? $resolution['x'] : NULL;

        case 'mime':
          return $imagick->getImageMimeType();

        case ($this->decorated)::METADATA_ATTRIBUTE_WIDTH:
          $width = $imagick->getImageWidth();
          return $width ? $width : FALSE;

        case ($this->decorated)::METADATA_ATTRIBUTE_HEIGHT:
          $height = $imagick->getImageHeight();
          return $height ? $height : FALSE;

      }
    }

    return $this->decorated->getMetadata($media, $name);
  }

}
