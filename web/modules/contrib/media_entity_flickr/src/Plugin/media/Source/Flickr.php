<?php

namespace Drupal\media_entity_flickr\Plugin\media\Source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;
use Drupal\media\MediaSourceFieldConstraintsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;

/**
 * Provides media type plugin for Flickr.
 *
 * @MediaSource(
 *   id = "flickr",
 *   label = @Translation("Flickr"),
 *   description = @Translation("Provides business logic and metadata for Flickr."),
 *   allowed_field_types = {"string", "string_long", "link"},
 *   default_thumbnail_filename = "flickr.png"
 * )
 */
class Flickr extends MediaSourceBase implements MediaSourceFieldConstraintsInterface {

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface|\Drupal\media_entity_flickr\Plugin\media\Source\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              EntityFieldManagerInterface $entity_field_manager,
                              FieldTypePluginManagerInterface $field_type_manager,
                              ConfigFactoryInterface $config_factory,
                              RendererInterface $renderer) {
    parent::__construct($configuration,
                        $plugin_id,
                        $plugin_definition,
                        $entity_type_manager,
                        $entity_field_manager,
                        $field_type_manager,
                        $config_factory);
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
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('renderer')
    );
  }

  /**
   * List of validation regular expressions.
   *
   * @var array
   */
  public static $validationRegexp = [
    // Album Regex.
    '@(?P<shortcode>(.*)  href=\"https://www.flickr.com/photos/(?<username>[^\s]+)/albums/(?<imageid>[0-9]+)\" title=\"(?P<title>[^.*]+)\"><img src=\"(?P<thumbnail>[^\s]+)\" width=\"(?P<width>[0-9]+)\" height=\"(?P<height>[0-9]+)\" alt=\"(.*)\"></a>(.*))@i' => 'shortcode',

    // Image Regex.
    '@(?P<shortcode>(.*)  href=\"https://www.flickr.com/photos/(?<username>[^\s]+)/(?<imageid>[0-9]+)/(.*)\" title=\"(?P<title>[^.*]+)\"><img src=\"(?P<thumbnail>[^\s]+)\" width=\"(?P<width>[0-9]+)\" height=\"(?P<height>[0-9]+)\" alt=\"(.*)\"></a>(.*))@i' => 'shortcode',
  ];

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $fields = [
      'shortcode' => $this->t('Flickr shortcode'),
      'username' => $this->t('Author of the post'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    if ($attribute_name == 'default_name') {
      // Try to get some fields that need the API, if not available, just use
      // the shortcode as default name.
      $username = $this->getMetadata($media, 'username');
      $id = $this->getMetadata($media, 'id');

      if ($username && $id) {
        return $username . ' - ' . $id;
      }
      else {
        $code = $this->getMetadata($media, 'shortcode');
        if (!empty($code)) {
          return $code;
        }
      }
      // Fallback to the parent's default name if everything else failed.
      return parent::getMetadata($media, 'default_name');
    }
    elseif ($attribute_name == 'thumbnail_uri') {
      return $this->getMetadata($media, 'thumbnail_local');
    }

    $matches = $this->matchRegexp($media);

    if (!$matches['shortcode']) {
      return FALSE;
    }

    if ($attribute_name == 'shortcode') {
      return $matches['shortcode'];
    }

    switch ($attribute_name) {
      case 'id':
        if (isset($matches['imageid'])) {
          return $matches['imageid'];
        }
        return FALSE;

      case 'thumbnail':
        if (isset($matches['thumbnail'])) {
          return $matches['thumbnail'];
        }
        return FALSE;

      case 'thumbnail_local':
        $local_uri = $this->getMetadata($media, 'thumbnail_local_uri');

        if ($local_uri) {
          if (file_exists($local_uri)) {
            return $local_uri;
          }
          else {
            $directory = $this->configFactory->get('media_entity_flickr.settings')->get('local_images');
            if (!file_exists($directory)) {
              file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
            }

            $image_url = $this->getMetadata($media, 'thumbnail');

            // TODO Changeme with Guzzle
            $image_data = file_get_contents($image_url);
            if ($image_data) {
              return file_unmanaged_save_data($image_data, $local_uri, FILE_EXISTS_REPLACE);
            }
          }
        }
        return FALSE;

      case 'thumbnail_local_uri':
        if (isset($matches['thumbnail'])) {
          $file_info = pathinfo($matches['thumbnail']);
          return $this->configFactory->get('media_entity_flickr.settings')->get('local_images') . '/' . $file_info['filename'] . '.' . $file_info['extension'];
        }
        return FALSE;

      case 'caption':
        if (isset($matches['title'])) {
          return $matches['title'];
        }
        return FALSE;

      case 'username':
        if (isset($matches['username'])) {
          return $matches['username'];
        }
        return FALSE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return ['FlickrEmbedCode' => []];
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type)->set('label', 'Flickr Url');
  }

  /**
   * Runs preg_match on embed code/URL.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media object.
   *
   * @return array|bool
   *   Array of preg matches or FALSE if no match.
   *
   * @see preg_match()
   */
  protected function matchRegexp(MediaInterface $media) {
    $matches = [];

    if (isset($this->configuration['source_field'])) {
      $source_field = $this->configuration['source_field'];
      if ($media->hasField($source_field)) {
        $property_name = $media->{$source_field}->first()->mainPropertyName();
        foreach (static::$validationRegexp as $pattern => $key) {
          if (preg_match($pattern, $media->{$source_field}->{$property_name}, $matches)) {
            return $matches;
          }
        }
      }
    }
    return FALSE;
  }

}
