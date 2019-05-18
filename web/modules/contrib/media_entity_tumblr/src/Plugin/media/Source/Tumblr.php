<?php

namespace Drupal\media_entity_tumblr\Plugin\media\Source;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides media source plugin for Tumblr.
 *
 * @todo On the long run we could switch to the tumblr API which provides WAY
 *   more fields.
 * @todo Support embed codes
 *
 * @MediaSource(
 *   id = "tumblr",
 *   label = @Translation("Tumblr"),
 *   description = @Translation("Provides business logic and metadata for Tumblr."),
 *   default_thumbnail_filename  = "tumblr_logo_blue-white-128.png",
 *   allowed_field_types = {"string", "string_long", "link"},
 * )
 *
 */
class Tumblr extends MediaSourceBase implements ContainerFactoryPluginInterface {

  /**
   * The Guzzle HTTP Client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

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
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ConfigFactoryInterface $config_factory, Client $http_client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->httpClient = $http_client;
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
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'author_name' => $this->t('Author name'),
      'width' => $this->t('Width'),
      'height' => $this->t('Height'),
      'url' => $this->t('Url'),
      'html' => $this->t('Html'),
      'default_name' => $this->t('Default name'),
      'thumbnail_uri' => $this->t('Thumbnail uri'),
    ];
  }

  /**
   * Returns the oembed data.
   *
   * @param string $url
   *   The URL to the tumblr post.
   *
   * @return array|false
   *  Get array with oembed data or FALSE if request fails.
   */
  protected function oEmbed($url) {
    $url = 'https://www.tumblr.com/oembed/1.0?url=' . $url;

    try {
      $response = $this->httpClient->get($url);
      return Json::decode((string) $response->getBody(), TRUE);
    }
    catch (RequestException $e) {
      return FALSE;
    }
  }

  /**
   * Runs preg_match on embed code/URL.
   *
   * @param MediaInterface $media
   *   Media object.
   *
   * @return string|false
   *   The tumblr URL or FALSE if there is no field.
   */
  protected function getTumblrUrl(MediaInterface $media) {
    $source_field = $this->getSourceFieldDefinition($media->bundle->entity)->getName();
    if ($media->hasField($source_field)) {
      $property_name = $media->{$source_field}->first()->mainPropertyName();
      return $media->{$source_field}->{$property_name};
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    $data = $this->oEmbed($this->getTumblrUrl($media));

    switch ($name) {
      case 'author_name':
        return $data['author_name'];
      case 'width':
        return $data['width'];
      case 'height':
        return $data['height'];
      case 'url':
        return $data['url'];
      case 'html':
        return $data['html'];
      default:
        return parent::getMetadata($media, $name);
    }
  }
}
